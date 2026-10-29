<?php
namespace App\EventSubscriber;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Exception\LogoutException;
final class LogoutFailureSubscriber implements EventSubscriberInterface
{
    public function __construct(private UrlGeneratorInterface $urls) {}
    public static function getSubscribedEvents(): array { return [KernelEvents::EXCEPTION=>['onException',20],KernelEvents::REQUEST=>['onRequest',20]]; }
    public function onRequest(\Symfony\Component\HttpKernel\Event\RequestEvent $event): void {
        $request=$event->getRequest();
        if(!$event->isMainRequest() || $request->getPathInfo()!==\App\Security\AdminArea::PATH.'/logout' || $request->isMethod('POST'))return;
        // Symfony's firewall matches logout by path, so enforce POST before its listener runs.
        if($request->isMethod('GET') || $request->isMethod('HEAD'))$event->setResponse(new RedirectResponse($this->urls->generate('app_logout_confirm')));
        else $event->setResponse(new \Symfony\Component\HttpFoundation\Response('',405,['Allow'=>'POST']));
    }
    public function onException(ExceptionEvent $event): void {
        if($event->getThrowable() instanceof LogoutException && $event->getRequest()->attributes->get('_route')==='app_logout')
            $event->setResponse(new RedirectResponse($this->urls->generate('app_logout_confirm')));
    }
}
