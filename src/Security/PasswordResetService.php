<?php
namespace App\Security;
use App\Entity\AdminUser;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\RateLimiter\RateLimiterFactory;
final class PasswordResetService
{
    public function __construct(private EntityManagerInterface $em,private MailerInterface $mailer,private UserPasswordHasherInterface $hasher,
        #[Autowire(service:'limiter.password_reset_ip')] private RateLimiterFactory $ipLimiter,
        #[Autowire(service:'limiter.password_reset_account')] private RateLimiterFactory $accountLimiter,
        #[Autowire('%kernel.secret%')] private string $secret,
        #[Autowire('%env(DEFAULT_URI)%')] private string $base,
        #[Autowire('%env(LOGIN_MAIL_FROM)%')] private string $sender,
        #[Autowire('%kernel.environment%')] private string $environment){}
    public function request(string $email,string $ip,string $locale): void {
        $email=strtolower(trim($email));
        $ipAllowed=$this->ipLimiter->create(hash_hmac('sha256',$ip,$this->secret))->consume()->isAccepted();
        $accountAllowed=$this->accountLimiter->create(hash_hmac('sha256',$email,$this->secret))->consume()->isAccepted();
        if(!$ipAllowed||!$accountAllowed||strlen($email)>180||!filter_var($email,FILTER_VALIDATE_EMAIL))return;
        $user=$this->em->getRepository(AdminUser::class)->findOneBy(['email'=>$email]);if(!$user||!$user->enabled)return;
        $this->deliver($user,$locale,false);
    }
    public function invitation(AdminUser $user,string $ip,string $locale):bool {
        if(!$user->enabled||!$user->id)return false;
        $allowed=$this->ipLimiter->create(hash_hmac('sha256','invite:'.$ip,$this->secret))->consume()->isAccepted();
        $account=$this->accountLimiter->create(hash_hmac('sha256',$user->email,$this->secret))->consume()->isAccepted();
        return $allowed&&$account&&$this->deliver($user,$locale,true);
    }
    private function deliver(AdminUser $user,string $locale,bool $invitation):bool {
        // Never construct reset URLs from the request Host header.
        if($this->environment==='prod' && !str_starts_with($this->base,'https://'))return false;
        $token=bin2hex(random_bytes(32));$hash=hash('sha256',$token);$db=$this->em->getConnection();
        $db->transactional(function()use($db,$user,$hash){
            $db->delete('admin_password_reset',['user_id'=>$user->id]);
            $db->insert('admin_password_reset',['user_id'=>$user->id,'token_hash'=>$hash,'expires_at'=>time()+1800,'password_version'=>hash('sha256',$user->password)]);
        });
        $url=rtrim($this->base,'/').AdminArea::PATH.'/reset-password?token='.$token.'&_locale='.($locale==='en'?'en':'de');
        $intro=$invitation?"You have been invited to manage the Care Studio website. Choose your own password using this link. Login codes will be sent to this email address.\nSie wurden zur Verwaltung der Care Studio-Website eingeladen. Legen Sie über diesen Link Ihr eigenes Passwort fest. Anmeldecodes werden an diese E-Mail-Adresse gesendet.\n\n":"Reset your password / Passwort zurücksetzen:\n";
        try{$this->mailer->send((new Email())->from($this->sender)->to($user->email)->subject($invitation?'Care Studio — account invitation / Kontoeinladung':'Care Studio — reset your password / Passwort zurücksetzen')->text($intro.$url."\n\nThis link expires in 30 minutes and can be used once. If you did not request it, ignore this email; your password has not changed.\nDieser Link ist 30 Minuten gültig und einmal verwendbar. Falls Sie dies nicht angefordert haben, ignorieren Sie diese E-Mail. Ihr Passwort wurde nicht geändert."));return true;}
        catch(\Throwable){$db->delete('admin_password_reset',['token_hash'=>$hash]);return false;}
    }
    public function hashToken(string $token): ?string {return preg_match('/^[a-f0-9]{64}$/D',$token)?hash('sha256',$token):null;}
    private function record(string $hash): array|false {return $this->em->getConnection()->fetchAssociative('SELECT user_id, password_version FROM admin_password_reset WHERE token_hash = ? AND expires_at > ?',[$hash,time()]);}
    public function valid(string $hash): bool {
        $row=$this->record($hash);if(!$row)return false;
        $user=$this->em->find(AdminUser::class,(int)$row['user_id']);return $user && $user->enabled && hash_equals($row['password_version'],hash('sha256',$user->password));
    }
    public function reset(string $hash,string $password): bool {
        if(mb_strlen($password)<14||strlen($password)>72)throw new \InvalidArgumentException('Password length outside policy.');
        $row=$this->record($hash);if(!$row)return false;
        $user=$this->em->find(AdminUser::class,(int)$row['user_id']);if(!$user||!$user->enabled||!hash_equals($row['password_version'],hash('sha256',$user->password)))return false;
        $newHash=$this->hasher->hashPassword($user,$password);$oldHash=$user->password;$db=$this->em->getConnection();
        $done=$db->transactional(function()use($db,$user,$hash,$oldHash,$newHash){
            if($db->executeStatement('DELETE FROM admin_password_reset WHERE user_id = ? AND token_hash = ? AND expires_at > ?',[$user->id,$hash,time()])!==1)return false;
            if($db->executeStatement('UPDATE AdminUser SET password = ?, passwordSet = 1, securityVersion = securityVersion + 1 WHERE id = ? AND password = ? AND enabled = 1',[$newHash,$user->id,$oldHash])!==1)throw new \RuntimeException('Password changed concurrently.');
            $db->delete('admin_email_challenge',['user_id'=>$user->id]);return true;
        });
        if($done){$this->em->clear();try{$this->mailer->send((new Email())->from($this->sender)->to($user->email)->subject('Care Studio — password changed / Passwort geändert')->text("Your administration password was changed. All previous sessions and login codes are no longer valid. If this was not you, contact your site administrator immediately.\nIhr Passwort wurde geändert. Bisherige Sitzungen und Anmeldecodes sind nicht mehr gültig. Falls Sie dies nicht waren, kontaktieren Sie umgehend Ihren Administrator."));}catch(\Throwable){}}
        return $done;
    }
}
