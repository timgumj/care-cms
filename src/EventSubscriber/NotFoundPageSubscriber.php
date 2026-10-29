<?php
namespace App\EventSubscriber;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Twig\Environment;
final class NotFoundPageSubscriber implements EventSubscriberInterface
{
 public function __construct(private Environment $twig){}
 public static function getSubscribedEvents():array{return [KernelEvents::EXCEPTION=>['show',-80]];}
 public function show(ExceptionEvent $event):void {
  $exception=$event->getThrowable();$request=$event->getRequest();
  if(!$exception instanceof HttpExceptionInterface || $exception->getStatusCode()!==404 || $request->getPreferredFormat()!=='html')return;
  $english=str_starts_with($request->getPathInfo(),'/en/') || (\App\Security\AdminArea::matches($request->getPathInfo()) && str_starts_with($request->getLocale(),'en'));
  $event->setResponse(new Response($this->twig->render('errors/base.html.twig',['status_code'=>404,'error_english'=>$english]),404,
    ['Content-Type'=>'text/html; charset=UTF-8','Content-Language'=>$english?'en-AT':'de-AT','X-Robots-Tag'=>'noindex, nofollow','Cache-Control'=>'private, no-store']));
 }
}
