<?php
namespace App\EventSubscriber;
use App\Security\AdminArea;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\{RequestEvent,ResponseEvent};
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelEvents;
final class SecurityHeadersSubscriber implements EventSubscriberInterface
{
    public function __construct(#[Autowire('%kernel.environment%')] private string $environment, #[Autowire('%env(bool:PREVIEW_ENABLED)%')] private bool $previewEnabled = false) {}
    public static function getSubscribedEvents(): array { return [KernelEvents::REQUEST=>['request',40], KernelEvents::RESPONSE=>['response',-10]]; }
    public function request(RequestEvent $event): void {
        if(!$event->isMainRequest())return;
        $r=$event->getRequest(); $p=$r->getPathInfo();
        if(preg_match('~^/(?:admin|wp-admin|wp-login\\.php|_profiler|_wdt)(?:/|$)~i',$p) || preg_match('~(?:^|/)\\.(?!well-known(?:/|$))~',$p))throw new NotFoundHttpException();
        // HTTP is allowed only for the local development server.
        if($this->environment==='prod'&&!$r->isSecure())throw new \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException('HTTPS required.');
    }
    public function response(ResponseEvent $event): void {
        if(!$event->isMainRequest())return;
        $r=$event->getRequest();$h=$event->getResponse()->headers;
        $h->set('X-Content-Type-Options','nosniff');$h->set('X-Frame-Options','SAMEORIGIN');
        $h->set('Referrer-Policy','strict-origin-when-cross-origin');
        $h->set('Permissions-Policy','camera=(), microphone=(), geolocation=()');
        // Compatible enforced baseline; script restrictions are report-only until the builder is fully exercised.
        $h->set('Content-Security-Policy',"object-src 'none'; base-uri 'self'; frame-ancestors 'self'; form-action 'self'");
        $h->set('Content-Security-Policy-Report-Only',"default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline'; img-src 'self' data: https:; font-src 'self' data:; connect-src 'self'; frame-src 'self' https://www.google.com https://maps.google.com; object-src 'none'; base-uri 'self'");
        if($this->environment==='prod' && $r->isSecure())$h->set('Strict-Transport-Security','max-age=31536000');
        if(!AdminArea::matches($r->getPathInfo()))$h->set('Content-Security-Policy',"default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline'; img-src 'self' data: https:; font-src 'self' data:; frame-src https://www.google.com https://maps.google.com; object-src 'none'; base-uri 'self'; frame-ancestors 'self'; form-action 'self'");
        if(AdminArea::matches($r->getPathInfo())){
            $h->set('Cache-Control','private, no-store, max-age=0');$h->set('Pragma','no-cache');
            $h->set('X-Robots-Tag','noindex, nofollow, noarchive');$h->set('Referrer-Policy','no-referrer');
        }
        if($this->environment!=='prod' || $event->getResponse()->getStatusCode()>=400)$h->set('X-Robots-Tag','noindex, nofollow');
        if($this->previewEnabled){
            $h->set('X-Robots-Tag','noindex, nofollow, noarchive');
            $h->set('Cache-Control','private, no-store, max-age=0');
            if($r->getPathInfo()==='/robots.txt')$event->getResponse()->setContent("User-agent: *\nDisallow: /\n");
        }
        if(str_starts_with($r->getPathInfo(),'/booking/')){$h->set('Cache-Control','private, no-store');$h->set('Referrer-Policy','no-referrer');$h->set('X-Robots-Tag','noindex, nofollow');}
        $h->remove('X-Powered-By');
    }
}
