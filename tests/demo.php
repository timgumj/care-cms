<?php
require __DIR__.'/../vendor/autoload.php';
use App\Service\BookingService;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\{EntityManager,ORMSetup};
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\HttpFoundation\Request;
function expect(bool $value,string $why):void{if(!$value)throw new RuntimeException($why);}
function refuse(callable $fn):void{try{$fn();throw new LogicException('Unsafe action accepted');}catch(DomainException){}}
$db=DriverManager::getConnection(['driver'=>'pdo_sqlite','memory'=>true]);
foreach(glob(__DIR__.'/../migrations/*.php') as $path){require_once $path;$class='DoctrineMigrations\\'.basename($path,'.php');$before=$db->createSchemaManager()->introspectSchema();$after=clone $before;$migration=new $class($db,new Psr\Log\NullLogger());$migration->up($after);foreach($migration->getSql() as $query)$db->executeStatement($query->getStatement(),$query->getParameters(),$query->getTypes());$diff=$db->createSchemaManager()->createComparator()->compareSchemas($before,$after);foreach($db->getDatabasePlatform()->getAlterSchemaSQL($diff) as $sql)$db->executeStatement($sql);}
expect($db->createSchemaManager()->tablesExist(['Page','AdminUser','appointment','booking_schedule']),'Migrations incomplete');
$mailer=new class implements Symfony\Component\Mailer\MailerInterface{public array $sent=[];public function send(Symfony\Component\Mime\RawMessage $message,?Symfony\Component\Mailer\Envelope $envelope=null):void{$this->sent[]=$message;}};
$booking=new BookingService($db,$mailer,'Europe/Vienna','https://demo.example.test','sender@example.test');
$day=new DateTimeImmutable('tomorrow',$booking->zone());while((int)$day->format('N')>5)$day=$day->modify('+1 day');$date=$day->format('Y-m-d');
$slots=$booking->slots($date,'consultation');expect(count($slots)>0,'No working-day slots');$start=$slots[0]['timestamp'];
expect(!$booking->slots('2026-02-30','consultation')&&!$booking->slots($date,'bad-type')&&!$booking->slots('2000-01-01','consultation'),'Invalid slot query accepted');
refuse(fn()=>$booking->book($date,'consultation',$start,'A','not-an-email'));
$id=$booking->book($date,'consultation',$start,'Fictional Visitor','visitor@example.test');
expect($id>0&&count($mailer->sent)===1,'Booking/mail failed');
refuse(fn()=>$booking->book($date,'follow-up',$start+900,'Other Visitor','other@example.test'));
expect((int)$db->fetchOne('SELECT COUNT(*) FROM appointment')===1,'Overlapping booking persisted');
preg_match('~/booking/cancel/([a-f0-9]{64})~',$mailer->sent[0]->getTextBody(),$match);$token=$match[1]??'';
expect(strlen($token)===64&&$db->fetchOne('SELECT cancel_hash FROM appointment')!==$token,'Cancellation secret stored in plaintext');
expect($booking->cancellation($token)!==null&&!$booking->cancellation(str_repeat('a',64)),'Cancellation lookup unsafe');
expect($booking->cancel($token)&&!$booking->cancel($token)&&!$booking->cancellation($token),'Cancel token replay');
expect(in_array($start,array_column($booking->slots($date,'consultation'),'timestamp'),true),'Cancelled slot not released');
$booking->saveSettings([1,2,3,4,5],'10:00','12:00',[$date]);expect(!$booking->slots($date,'consultation'),'Blocked date offered');
refuse(fn()=>$booking->saveSettings([8],'10:00','12:00',[]));refuse(fn()=>$booking->saveSettings([1],'18:00','09:00',[]));
refuse(fn()=>$booking->saveSettings([1],'09:00','17:00',['2026-02-30']));
$booking->saveSettings([1,2,3,4,5],'09:00','17:00',[]);
$timezoneSlots=$booking->slots($date,'follow-up');expect($timezoneSlots[0]['iso']===(new DateTimeImmutable('@'.$timezoneSlots[0]['timestamp']))->setTimezone($booking->zone())->format(DATE_ATOM),'Timezone mismatch');
// ORM content + actual public API serializer: drafts, trash, fields, CORS and input checks.
$config=ORMSetup::createAttributeMetadataConfiguration([__DIR__.'/../src/Entity'],true,null,new ArrayAdapter());$em=new EntityManager($db,$config);
foreach([['/published/',true,false],['/draft/',false,false],['/trash/',true,true]] as [$path,$published,$trashed]){$page=new App\Entity\Page();$page->path=$path;$page->title='Fictional';$page->description='Example';$page->locale='en-AT';$page->canonical='https://demo.example.test'.$path;$page->content='<p>Safe content</p><script>alert(1)</script>';$page->published=$published;if($trashed)$page->deletedAt=new DateTimeImmutable();$em->persist($page);}$em->flush();
$api=new App\Controller\ContentApiController();$api->setContainer(new Symfony\Component\DependencyInjection\Container());$sanitizer=new App\Security\EditorContentSanitizer();
$request=Request::create('/api/v1/pages?locale=en-AT');$request->headers->set('Origin','http://127.0.0.1:5173');$response=$api->pages($request,$em,$sanitizer,'http://127.0.0.1:5173');$data=json_decode($response->getContent(),true,512,JSON_THROW_ON_ERROR);
expect(count($data['items'])===1&&$data['items'][0]['path']==='/published/','Unpublished/trash API leak');
expect(!str_contains($data['items'][0]['html'],'script')&&!isset($data['items'][0]['email'])&&!isset($data['items'][0]['headerHtml']),'API serialization boundary');
expect($response->headers->get('Access-Control-Allow-Origin')==='http://127.0.0.1:5173'&&!$response->headers->has('Access-Control-Allow-Credentials'),'Unsafe CORS');
$request->headers->set('Origin','https://evil.example');expect(!$api->pages($request,$em,$sanitizer,'http://127.0.0.1:5173')->headers->has('Access-Control-Allow-Origin'),'Untrusted origin allowed');
expect($api->pages(Request::create('/api/v1/pages?locale=bad'),$em,$sanitizer,'')->getStatusCode()===400,'Invalid API locale accepted');
// New dashboard controller must remain administrator-only, independent of menu visibility.
$role=(new ReflectionClass(App\Controller\Admin\CalendarController::class))->getAttributes(Symfony\Component\Security\Http\Attribute\IsGranted::class)[0]->newInstance()->attribute;expect($role==='ROLE_ADMIN','Appointment data exposed to editors');
// Parse new templates without filesystem permission workarounds or network calls.
$templates=[];foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator(__DIR__.'/../templates')) as $file)if($file->isFile()&&str_ends_with($file->getFilename(),'.twig'))$templates[str_replace('\\','/',substr($file->getPathname(),strlen(__DIR__.'/../templates/')))]=file_get_contents($file->getPathname());
$twig=new Twig\Environment(new Twig\Loader\ArrayLoader($templates),['strict_variables'=>true]);foreach(['path','csrf_token','is_granted'] as $name)$twig->addFunction(new Twig\TwigFunction($name,fn()=>''));$twig->addFilter(new Twig\TwigFilter('trans',fn($s)=>$s));
foreach(['site/base.html.twig','site/page.html.twig','site/_navigation.html.twig','booking/index.html.twig','booking/cancel.html.twig','admin/calendar.html.twig'] as $name)$twig->parse($twig->tokenize(new Twig\Source($templates[$name],$name)));
$nav=$twig->render('site/_navigation.html.twig',['english'=>true,'items'=>[],'language_links'=>['de'=>'/','en'=>'/en/'],'current_locale'=>'en']);expect(str_contains($nav,'CARE STUDIO'),'Demo navigation missing');
echo "PASS: eight migrations; booking validation/overlap/cancellation/availability/timezone; published-only API/XSS/CORS; admin-only calendar; demo template parsing. No real mail sent.\n";
