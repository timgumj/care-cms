<?php
require __DIR__.'/../vendor/autoload.php';
use Doctrine\DBAL\DriverManager;
use App\Service\BookingService;
if(($argv[1]??'')==='worker'){
 $db=DriverManager::getConnection(['driver'=>'pdo_sqlite','path'=>$argv[2],'driverOptions'=>[PDO::ATTR_TIMEOUT=>5]]);
 $mailer=new class implements Symfony\Component\Mailer\MailerInterface{public function send(Symfony\Component\Mime\RawMessage $message,?Symfony\Component\Mailer\Envelope $envelope=null):void{}};
 $service=new BookingService($db,$mailer,'Europe/Vienna','http://127.0.0.1:8020','demo@example.test');
 try{$service->book($argv[3],'consultation',(int)$argv[4],'Concurrent Demo','demo@example.test');echo 'booked';}catch(DomainException){echo 'unavailable';}exit;
}
$file=__DIR__.'/../var/concurrency-'.bin2hex(random_bytes(6)).'.db';
$db=DriverManager::getConnection(['driver'=>'pdo_sqlite','path'=>$file]);
require __DIR__.'/../migrations/Version20261029000100.php';$migration=new DoctrineMigrations\Version20261029000100($db,new Psr\Log\NullLogger());$migration->up(new Doctrine\DBAL\Schema\Schema());foreach($migration->getSql() as $query)$db->executeStatement($query->getStatement());
$day=new DateTimeImmutable('tomorrow',new DateTimeZone('Europe/Vienna'));while((int)$day->format('N')>5)$day=$day->modify('+1 day');$date=$day->format('Y-m-d');$stamp=$day->setTime(10,0)->getTimestamp();
$processes=[];
try{
 for($i=0;$i<2;$i++){$pipes=[];$process=proc_open([PHP_BINARY,__FILE__,'worker',$file,$date,(string)$stamp],[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes);if(!is_resource($process))throw new RuntimeException('Cannot start concurrency worker');fclose($pipes[0]);$processes[]=[$process,$pipes];}
 $results=[];foreach($processes as [$process,$pipes]){$results[]=trim(stream_get_contents($pipes[1]));$error=stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);if(proc_close($process)!==0)throw new RuntimeException('Worker failure: '.$error);}
 sort($results);if($results!==['booked','unavailable']||(int)$db->fetchOne('SELECT COUNT(*) FROM appointment')!==1)throw new RuntimeException('Concurrent double booking accepted: '.json_encode($results));
 echo "PASS: two independent PHP processes compete for one slot; exactly one booking is committed.\n";
}finally{$db->close();if(is_file($file))unlink($file);}
