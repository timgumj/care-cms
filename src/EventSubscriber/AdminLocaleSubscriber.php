<?php
namespace App\EventSubscriber;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpKernel\Event\{RequestEvent,ResponseEvent};
use Symfony\Component\HttpKernel\KernelEvents;
class AdminLocaleSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array { return [KernelEvents::REQUEST=>['onRequest',15],KernelEvents::RESPONSE=>['onResponse',0]]; }
    public function onRequest(RequestEvent $event): void {
        if(!$event->isMainRequest())return;
        $request=$event->getRequest();if(!\App\Security\AdminArea::matches($request->getPathInfo()))return;
        $query=$request->query->all()['_locale']??null;
        $saved=$request->cookies->get('care_admin_language','de');
        if($request->hasSession())$saved=$request->getSession()->get('_admin_locale',$saved);
        $locale=in_array($query,['de','en'],true)?$query:(in_array($saved,['de','en'],true)?$saved:'de');
        if($request->hasSession())$request->getSession()->set('_admin_locale',$locale);
        $request->setLocale($locale);$request->attributes->set('_locale',$locale);
        if(in_array($query,['de','en'],true))$request->attributes->set('_admin_locale_selected',true);
    }
    public function onResponse(ResponseEvent $event): void {
        $request=$event->getRequest();if(!$event->isMainRequest()||!$request->attributes->get('_admin_locale_selected'))return;
        $event->getResponse()->headers->setCookie(Cookie::create('care_admin_language',$request->getLocale(),expire:new \DateTimeImmutable('+1 year'),path:\App\Security\AdminArea::PATH,secure:$request->isSecure(),httpOnly:true,sameSite:'lax'));
    }
}

