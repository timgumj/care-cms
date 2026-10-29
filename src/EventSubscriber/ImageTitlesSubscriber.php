<?php
namespace App\EventSubscriber;
use App\Security\AdminArea;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
final class ImageTitlesSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array {return [KernelEvents::RESPONSE=>['remove',-20]];}
    public function remove(ResponseEvent $event): void {
        if(!$event->isMainRequest()||AdminArea::matches($event->getRequest()->getPathInfo()))return;
        $response=$event->getResponse();
        if(!str_contains($response->headers->get('Content-Type',''),'text/html'))return;
        $html=$response->getContent();if($html===false)return;
        $html=preg_replace_callback('~<img\b(?:"[^"]*"|\'[^\']*\'|[^\'">])*>~i',static function($match){
            return preg_replace_callback('~\s+([^\s=/>]+)(?:\s*=\s*(?:"[^"]*"|\'[^\']*\'|[^\s>]+))?~',static fn($attribute)=>strtolower($attribute[1])==='title'?'':$attribute[0],$match[0]);
        },$html);
        $response->setContent($html);
    }
}
