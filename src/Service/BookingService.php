<?php
namespace App\Service;
use Doctrine\DBAL\Connection;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

/** Single-practitioner, SQLite-first demo. All appointment times are UTC Unix seconds. */
final class BookingService
{
 public const TYPES=['consultation'=>['label'=>'Consultation / Beratung','minutes'=>30], 'follow-up'=>['label'=>'Follow-up / Folgetermin','minutes'=>15]];
 public function __construct(private Connection $db, private MailerInterface $mailer,
  #[Autowire('%env(BOOKING_TIMEZONE)%')] private string $timezone,
  #[Autowire('%env(DEFAULT_URI)%')] private string $baseUri,
  #[Autowire('%env(LOGIN_MAIL_FROM)%')] private string $sender) {}
 public function zone(): \DateTimeZone { return new \DateTimeZone($this->timezone); }
 public function settings(): array {
  $value=json_decode($this->db->fetchOne('SELECT settings FROM booking_schedule WHERE id=1'),true,512,JSON_THROW_ON_ERROR);
  return $value+['days'=>[1,2,3,4,5],'opens'=>'09:00','closes'=>'17:00','blocked'=>[]];
 }
 public function saveSettings(array $days,string $opens,string $closes,array $blocked): void {
  if(!$days || array_diff($days,[1,2,3,4,5,6,7]) || !preg_match('/^([01]\d|2[0-3]):[0-5]\d$/D',$opens) || !preg_match('/^([01]\d|2[0-3]):[0-5]\d$/D',$closes) || $opens>=$closes || count($blocked)>100)throw new \DomainException('Enter valid days, opening hours and blocked dates.');
  foreach($blocked as $date)if(!$this->date($date))throw new \DomainException('Blocked dates must use YYYY-MM-DD.');
  $this->db->transactional(function()use($days,$opens,$closes,$blocked){
   $this->db->executeStatement('UPDATE booking_schedule SET revision=revision+1 WHERE id=1');
   $this->db->update('booking_schedule',['settings'=>json_encode(['days'=>array_values(array_unique($days)),'opens'=>$opens,'closes'=>$closes,'blocked'=>array_values(array_unique($blocked))],JSON_THROW_ON_ERROR)],['id'=>1]);
  });
 }
 private function date(string $date): ?\DateTimeImmutable {
  $d=\DateTimeImmutable::createFromFormat('!Y-m-d',$date,$this->zone());return $d&&$d->format('Y-m-d')===$date?$d:null;
 }
 public function slots(string $date,string $type,?int $now=null): array {
  if(!isset(self::TYPES[$type]))return [];
  $day=$this->date($date);$now??=time();$today=(new \DateTimeImmutable('@'.$now))->setTimezone($this->zone())->setTime(0,0);
  if(!$day||$day<$today||$day>$today->modify('+60 days'))return [];
  $settings=$this->settings();
  if(!in_array((int)$day->format('N'),$settings['days'],true)||in_array($date,$settings['blocked'],true))return [];
  $start=new \DateTimeImmutable($date.' '.$settings['opens'],$this->zone());$end=new \DateTimeImmutable($date.' '.$settings['closes'],$this->zone());
  $minutes=self::TYPES[$type]['minutes'];$busy=$this->db->fetchAllAssociative("SELECT starts_at,ends_at FROM appointment WHERE status='confirmed' AND starts_at < ? AND ends_at > ?",[$end->getTimestamp(),$start->getTimestamp()]);
  $slots=[];
  for($slot=$start;$slot->modify('+'.$minutes.' minutes')<=$end;$slot=$slot->modify('+15 minutes')) {
   $a=$slot->getTimestamp();$b=$a+$minutes*60;if($a<$now+3600)continue;
   foreach($busy as $row)if($a<(int)$row['ends_at']&&$b>(int)$row['starts_at'])continue 2;
   $slots[]=['timestamp'=>$a,'label'=>$slot->format('H:i'),'iso'=>$slot->format(DATE_ATOM)];
  }
  return $slots;
 }
 public function book(string $date,string $type,int $start,string $name,string $email): int {
  $name=trim($name);$email=strtolower(trim($email));
  if(mb_strlen($name)<2||mb_strlen($name)>100||preg_match('/[\x00-\x1f\x7f]/',$name)||strlen($email)>180||!filter_var($email,FILTER_VALIDATE_EMAIL))throw new \DomainException('Enter a valid name and email address.');
  $token=bin2hex(random_bytes(32));
  // Acquiring a database write lock BEFORE reading slots serializes overlapping bookings.
  // This also coordinates separate PHP processes; browser-only checks are insufficient.
  $id=$this->db->transactional(function()use($date,$type,$start,$name,$email,$token){
   $this->db->executeStatement('UPDATE booking_schedule SET revision=revision+1 WHERE id=1');
   if(!in_array($start,array_column($this->slots($date,$type),'timestamp'),true))throw new \DomainException('That appointment is no longer available. Choose another slot.');
   $this->db->insert('appointment',['starts_at'=>$start,'ends_at'=>$start+self::TYPES[$type]['minutes']*60,'appointment_type'=>$type,'attendee_name'=>$name,'email'=>$email,'cancel_hash'=>hash('sha256',$token),'status'=>'confirmed','created_at'=>time()]);
   return (int)$this->db->lastInsertId();
  });
  $when=(new \DateTimeImmutable('@'.$start))->setTimezone($this->zone())->format('Y-m-d H:i T');
  try {$this->mailer->send((new Email())->from($this->sender)->to($email)->subject('Care Studio DEMO — appointment confirmation / Terminbestätigung')->text("DEMO ONLY — no real appointment / Kein echter Termin.\n\nYour appointment / Ihr Termin: $when\n\nCancel / Stornieren: ".rtrim($this->baseUri,'/').'/booking/cancel/'.$token."\n\nPlease do not send medical information. / Bitte keine medizinischen Daten senden."));}
  catch(\Throwable){throw new \RuntimeException('Your demo booking was saved, but the email could not be sent. Contact the demo administrator before trying again.');}
  return $id;
 }
 public function cancellation(string $token): ?array {
  if(!preg_match('/^[a-f0-9]{64}$/D',$token))return null;
  return $this->db->fetchAssociative("SELECT id,starts_at,status FROM appointment WHERE cancel_hash=? AND status='confirmed'",[hash('sha256',$token)])?:null;
 }
 public function cancel(string $token): bool {
  if(!$this->cancellation($token))return false;
  return $this->db->executeStatement("UPDATE appointment SET status='cancelled' WHERE cancel_hash=? AND status='confirmed'",[hash('sha256',$token)])===1;
 }
 public function cancelByAdmin(int $id): void { $this->db->executeStatement("UPDATE appointment SET status='cancelled' WHERE id=? AND status='confirmed'",[$id]); }
 public function month(string $month): array {
  $from=$this->date($month.'-01');if(!$from)throw new \DomainException('Invalid month.');
  return $this->db->fetchAllAssociative('SELECT id,starts_at,ends_at,appointment_type,attendee_name,email,status FROM appointment WHERE starts_at>=? AND starts_at<? ORDER BY starts_at',[$from->getTimestamp(),$from->modify('+1 month')->getTimestamp()]);
 }
}
