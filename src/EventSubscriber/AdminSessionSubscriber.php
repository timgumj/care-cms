<?php
namespace App\EventSubscriber;
use App\Security\AdminArea;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
final class AdminSessionSubscriber implements EventSubscriberInterface
{
    public function __construct(private TokenStorageInterface $tokens,private UrlGeneratorInterface $urls, #[\Symfony\Component\DependencyInjection\Attribute\Autowire('%kernel.environment%')] private string $environment, #[\Symfony\Component\DependencyInjection\Attribute\Autowire('%env(bool:EMAIL_2FA_ENABLED)%')] private bool $email2fa, private \App\Security\SessionSettings $settings){}
    public static function getSubscribedEvents(): array { return [KernelEvents::REQUEST=>['check',0],KernelEvents::RESPONSE=>['persist',-1001]]; }
    public function check(RequestEvent $event): void {
        $r=$event->getRequest();if(!$event->isMainRequest()||!$r->hasSession())return;
        $admin=AdminArea::matches($r->getPathInfo());if(!$admin&&!$r->hasPreviousSession())return;
        if(!$this->tokens->getToken()?->getUser())return;
        $s=$r->getSession();$now=time();$started=$s->get('_admin_started',$now);$last=$s->get('_admin_last',$now);
        $factorMissing=($this->environment==='prod'||$this->email2fa) && !$s->has('_email_verified_at') && !$this->tokens->getToken() instanceof \Scheb\TwoFactorBundle\Security\Authentication\Token\TwoFactorTokenInterface;
        $limits=$this->settings->read();
        if($factorMissing||$now-$last >= $limits['idle_minutes']*60||$now-$started >= $limits['maximum_hours']*3600){$this->tokens->setToken(null);$s->invalidate();if($admin)$event->setResponse(new RedirectResponse($this->urls->generate('app_login', ['_locale'=>$r->getLocale()])));return;}
        $s->set('_admin_started',$started);$s->set('_admin_last',$now);
        if(!$this->tokens->getToken() instanceof \Scheb\TwoFactorBundle\Security\Authentication\Token\TwoFactorTokenInterface){$r->attributes->set('_admin_session_active',true);$r->attributes->set('_admin_session_expires',$started+$limits['maximum_hours']*3600);}
    }
    public function persist(\Symfony\Component\HttpKernel\Event\ResponseEvent $event): void {
        if(!$event->isMainRequest())return;$request=$event->getRequest();if(str_contains($event->getResponse()->headers->get('Content-Type',''),'text/html'))$event->getResponse()->setVary('Cookie',false);if(!$request->attributes->get('_admin_session_active'))return;
        $session=$request->getSession();$response=$event->getResponse();
        $response->headers->setCookie(\Symfony\Component\HttpFoundation\Cookie::create($session->getName(),$session->getId(),(int)$request->attributes->get('_admin_session_expires'),'/',null,$this->environment==='prod'||$request->isSecure(),true,false,'lax'));
        $response->headers->set('Cache-Control','private, no-store, max-age=0');$response->setVary('Cookie',false);
    }
}
