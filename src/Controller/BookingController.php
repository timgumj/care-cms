<?php
namespace App\Controller;
use App\Service\BookingService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\{Request,Response,JsonResponse};
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;
final class BookingController extends AbstractController
{
 #[Route('/booking/',name:'booking',methods:['GET','POST'])]
 public function index(Request $r,BookingService $booking,#[Autowire(service:'limiter.booking')] RateLimiterFactory $limiter,\App\Service\StarterPacks $packs): Response {
  $english=$r->query->get('lang','en')==='en';$r->setLocale($english?'en':'de');$date=$r->get('date',(new \DateTimeImmutable('now',$booking->zone()))->modify('+1 day')->format('Y-m-d'));$type=$r->get('type','consultation');
  if(!is_string($date)||!is_string($type))throw $this->createNotFoundException();
  $error=null;
  if($r->isMethod('POST')) {
   if(!$this->isCsrfTokenValid('booking',$r->request->getString('_token')))throw new \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException('Invalid form token.');
   if(!$limiter->create((string)$r->getClientIp())->consume()->isAccepted())return new Response('Too many requests. Please wait 15 minutes.',429,['Retry-After'=>'900']);
   try {
    if($r->request->getString('consent')!=='yes')throw new \DomainException('Please acknowledge this is a fictional demo.');
    $booking->book($date,$type,$r->request->getInt('slot'),$r->request->getString('name'),$r->request->getString('email'));
    $this->addFlash('success','Demo appointment saved. Check the local mail inbox for confirmation. / Demo-Termin gespeichert.');
    return $this->redirectToRoute('booking',['lang'=>$english?'en':'de']);
   }catch(\DomainException $e){$error=$e->getMessage();}catch(\Throwable){$error='The request could not be completed. A booking may already have been saved; check your email or contact the demo administrator before retrying.';}
  }
  $response=$this->render('booking/index.html.twig',['english'=>$english,'date'=>$date,'type'=>$type,'types'=>BookingService::TYPES,'slots'=>$booking->slots($date,$type),'timezone'=>$booking->zone()->getName(),'error'=>$error]+$packs->bookingPresentation($english));
  $response->setPrivate();$response->headers->set('Cache-Control','private, no-store');return $response;
 }
 #[Route('/booking/slots',name:'booking_slots',methods:['GET'])]
 public function slots(Request $r,BookingService $booking): JsonResponse {
  return $this->json(['timezone'=>$booking->zone()->getName(),'slots'=>$booking->slots($r->query->getString('date'),$r->query->getString('type','consultation'))],200,['Cache-Control'=>'no-store']);
 }
 #[Route('/booking/cancel/{token}',name:'booking_cancel',requirements:['token'=>'[a-f0-9]{64}'],methods:['GET','POST'])]
 public function cancel(string $token,Request $r,BookingService $booking): Response {
  $item=$booking->cancellation($token);if(!$item)throw $this->createNotFoundException('Cancellation link expired or already used.');
  if($r->isMethod('POST')) {
   if(!$this->isCsrfTokenValid('cancel_'.$token,$r->request->getString('_token')))throw new \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException('Invalid form token.');
   $booking->cancel($token);$this->addFlash('success','Demo appointment cancelled. / Demo-Termin storniert.');return $this->redirectToRoute('booking');
  }
  return $this->render('booking/cancel.html.twig',['token'=>$token,'item'=>$item],new Response(headers:['Cache-Control'=>'no-store','Referrer-Policy'=>'no-referrer','X-Robots-Tag'=>'noindex']));
 }
}
