<?php
namespace App\EventSubscriber;
use App\Security\AdminArea;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Http\Event\{LoginFailureEvent,LoginSuccessEvent};
use Scheb\TwoFactorBundle\Security\TwoFactor\Event\{TwoFactorAuthenticationEvents,TwoFactorAuthenticationEvent};
final class SecurityAuditSubscriber implements EventSubscriberInterface
{
    public function __construct(#[Autowire('%kernel.project_dir%')] private string $root, #[Autowire('%kernel.secret%')] private string $secret){}
    public static function getSubscribedEvents(): array { return [LoginFailureEvent::class=>'failure',LoginSuccessEvent::class=>'success',TwoFactorAuthenticationEvents::FAILURE=>'codeFailure',TwoFactorAuthenticationEvents::COMPLETE=>'complete']; }
    private function record(string $event,\Symfony\Component\HttpFoundation\Request $r, ?string $reason=null):void {
        if(!AdminArea::matches($r->getPathInfo()))return;
        $dir=$this->root.'/var/log';if(!is_dir($dir))mkdir($dir,0700,true);
        // Never log passwords, codes, SMTP credentials, email addresses, or session identifiers.
        $entry=['time'=>gmdate('c'),'event'=>$event,'reason'=>$reason,'source'=>hash_hmac('sha256',$r->getClientIp()??'unknown',$this->secret)];
        $guard=fopen($dir.'/security.lock','c');if(!$guard||!flock($guard,LOCK_EX))throw new \RuntimeException('Audit log lock unavailable.');
        try{
            $file=$dir.'/security.jsonl';clearstatcache(true,$file);
            if(is_file($file)&&filesize($file)>=2*1024*1024){
                if(is_file($file.'.3'))unlink($file.'.3');
                for($i=2;$i>=1;$i--)if(is_file($file.'.'.$i))rename($file.'.'.$i,$file.'.'.($i+1));
                rename($file,$file.'.1');
            }
            file_put_contents($file,json_encode($entry)."\n",FILE_APPEND|LOCK_EX);
        }finally{flock($guard,LOCK_UN);fclose($guard);}
    }
    public function failure(LoginFailureEvent $e):void {$this->record('login_failed',$e->getRequest(),$e->getException()::class);}
    public function success(LoginSuccessEvent $e):void {$this->record('password_step_accepted',$e->getRequest());}
    public function codeFailure(TwoFactorAuthenticationEvent $e):void {$this->record('email_code_failed',$e->getRequest());}
    public function complete(TwoFactorAuthenticationEvent $e):void {$this->record('email_code_completed',$e->getRequest());}
}
