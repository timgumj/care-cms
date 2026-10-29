<?php
require __DIR__.'/../vendor/autoload.php';
use App\Entity\AdminUser;
use App\Security\{EmailCodeProvider,EmailCodeFormRenderer,EditorContentSanitizer};
use Doctrine\DBAL\DriverManager;
use Symfony\Component\HttpFoundation\{Request,RequestStack};
use Symfony\Component\HttpFoundation\Session\{Session,Storage\MockArraySessionStorage};
class CapturingMailer implements \Symfony\Component\Mailer\MailerInterface {
 public array $sent=[];
 public function send(\Symfony\Component\Mime\RawMessage $message, ?\Symfony\Component\Mailer\Envelope $envelope=null): void {$this->sent[]=$message;}
}
use Symfony\Component\RateLimiter\{RateLimiterFactory,Storage\InMemoryStorage};
function check(bool $condition,string $why): void { if(!$condition)throw new RuntimeException($why); }
$db=DriverManager::getConnection(['driver'=>'pdo_sqlite','memory'=>true]);
$db->executeStatement('CREATE TABLE admin_email_challenge (user_id INTEGER PRIMARY KEY, code_hash VARCHAR(64) NOT NULL, session_hash VARCHAR(64) NOT NULL, expires_at INTEGER NOT NULL, attempts INTEGER NOT NULL)');
$request=Request::create('/manage/login');$session=new Session(new MockArraySessionStorage());$request->setSession($session);$stack=new RequestStack();$stack->push($request);
$transport=$mailer=new CapturingMailer();
$renderer=new EmailCodeFormRenderer(new Twig\Environment(new Twig\Loader\ArrayLoader()));
$factory=new RateLimiterFactory(['id'=>'test','policy'=>'sliding_window','limit'=>3,'interval'=>'15 minutes'],new InMemoryStorage());
$provider=new EmailCodeProvider($db,$stack,$mailer,$renderer,$factory,str_repeat('x',64),'test',true,'sender@example.test','smtp://localhost:1025');
$user=new AdminUser();$user->id=99;$user->email='recipient@example.test';$user->password='dummy-hash-for-test-only';
function code(CapturingMailer $t): string { $sent=$t->sent;preg_match('/[0-9]{8}/',end($sent)->getTextBody(),$m);return $m[0]; }
$provider->prepareAuthentication($user);$first=code($transport);
check($db->fetchOne('SELECT code_hash FROM admin_email_challenge')!==$first,'Plaintext persisted');
check(!$provider->validateAuthenticationCode($user,'123'),'Malformed accepted');
$nonce=$session->get('_email_code_nonce');$session->set('_email_code_nonce',str_repeat('a',64));check(!$provider->validateAuthenticationCode($user,$first),'Other session accepted');$session->set('_email_code_nonce',$nonce);
check($provider->validateAuthenticationCode($user,$first),'Valid code refused');check(!$provider->validateAuthenticationCode($user,$first),'Replay accepted');
$provider->prepareAuthentication($user);$second=code($transport);$db->executeStatement('UPDATE admin_email_challenge SET expires_at = 0');check(!$provider->validateAuthenticationCode($user,$second),'Expired accepted');
$provider->prepareAuthentication($user);$third=code($transport);for($i=0;$i<5;$i++)check(!$provider->validateAuthenticationCode($user,'wrong'),'Wrong code accepted');check(!$provider->validateAuthenticationCode($user,$third),'Attempt cap bypassed');
try{$provider->prepareAuthentication($user);throw new RuntimeException('Send cap bypassed');}catch(Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException $e){}
$unsafe='<main><h1>Überschrift</h1><p class="x" style="color:red">Text</p><img src="/uploads/editor/test.png" onerror="alert(1)"><script>alert(1)</script><a href="javascript:alert(1)">bad</a><iframe src="https://evil.example/test" srcdoc="<script>alert(1)</script>"></iframe><style>.x{padding:20px}</style></main>';
$clean=(new EditorContentSanitizer())->sanitize($unsafe);
foreach(['<script','onerror','javascript:','evil.example','srcdoc'] as $bad)check(!str_contains($clean,$bad),'Unsafe HTML retained: '.$bad);
foreach(['Überschrift','class="x"','color:red','.x{padding:20px}','/uploads/editor/test.png'] as $good)check(str_contains($clean,$good),'Layout/content lost: '.$good);

$passport=new Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport(new Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge($user->email,fn()=>$user));
$token=new Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken($user,'main',['ROLE_ADMIN']);
$context=new Scheb\TwoFactorBundle\Security\TwoFactor\AuthenticationContext($request,$token,$passport,'main');
$prod=new EmailCodeProvider($db,$stack,$mailer,$renderer,$factory,str_repeat('x',64),'prod',false,'','null://null');
check($prod->beginAuthentication($context),'Production email verification disabled by local flag');
try{$prod->prepareAuthentication($user);throw new RuntimeException('Unconfigured production mail accepted');}catch(Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException $e){}
$routes=new Symfony\Component\Routing\RouteCollection();$routes->add('app_login',new Symfony\Component\Routing\Route('/manage/login'));
$urls=new Symfony\Component\Routing\Generator\UrlGenerator($routes,new Symfony\Component\Routing\RequestContext());
$tokens=new Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage();$tokens->setToken($token);
$guard=new App\EventSubscriber\AdminSessionSubscriber($tokens,$urls,'prod',false,new App\Security\SessionSettings(sys_get_temp_dir().'/care-defaults-'.bin2hex(random_bytes(8))));
$kernel=new App\Kernel('test',true);$event=new Symfony\Component\HttpKernel\Event\RequestEvent($kernel,$request,Symfony\Component\HttpKernel\HttpKernelInterface::MAIN_REQUEST);
$session->remove('_email_verified_at');$guard->check($event);check($event->hasResponse()&&$tokens->getToken()===null,'Legacy password-only session survived enforcement');
$tokens->setToken($token);$session->set('_email_verified_at',time());$session->set('_admin_last',time()-1801);$event=new Symfony\Component\HttpKernel\Event\RequestEvent($kernel,$request,1);$guard->check($event);check($event->hasResponse()&&$tokens->getToken()===null,'Idle timeout bypassed');
$tokens->setToken($token);$session->set('_email_verified_at',time());$session->set('_admin_last',time());$session->set('_admin_started',time()-28801);$event=new Symfony\Component\HttpKernel\Event\RequestEvent($kernel,$request,1);$guard->check($event);check($event->hasResponse(),'Absolute timeout bypassed');
echo "PASS: production cannot disable verification; unconfigured delivery denied; legacy, idle and absolute-expired sessions rejected.\n";
echo "PASS: hashed/session-bound codes, single use, expiry, five guesses, send cap, malicious HTML removal and safe editor layout preservation. No real emails sent.\n";
