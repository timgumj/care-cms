<?php
namespace App\Controller\Admin;
use App\Service\BookingService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{Request,Response};
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
#[IsGranted('ROLE_ADMIN')]
final class CalendarController extends AbstractController
{
 #[Route('/manage/calendar',name:'admin_calendar',methods:['GET','POST'])]
 public function index(Request $r,BookingService $booking): Response {
  $month=$r->query->getString('month',(new \DateTimeImmutable('now',$booking->zone()))->format('Y-m'));
  if(!preg_match('/^20\d{2}-(0[1-9]|1[0-2])$/D',$month))throw $this->createNotFoundException();
  $error=null;
  if($r->isMethod('POST')) {
   if(!$this->isCsrfTokenValid('calendar',$r->request->getString('_token')))throw $this->createAccessDeniedException();
   try {
    if($r->request->getString('action')==='cancel')$booking->cancelByAdmin($r->request->getInt('id'));
    elseif($r->request->getString('action')==='availability')$booking->saveSettings(array_map('intval',$r->request->all('days')),$r->request->getString('opens'),$r->request->getString('closes'),preg_split('/\s+/',trim($r->request->getString('blocked')),-1,PREG_SPLIT_NO_EMPTY));
    else throw new \DomainException('Invalid action.');
    return $this->redirectToRoute('admin_calendar',['month'=>$month]);
   }catch(\DomainException $e){$error=$e->getMessage();}
  }
  $rows=$booking->month($month);$byDay=[];foreach($rows as $row){$local=(new \DateTimeImmutable('@'.$row['starts_at']))->setTimezone($booking->zone());$row['local']=$local;$byDay[$local->format('Y-m-d')][]=$row;}
  $first=new \DateTimeImmutable($month.'-01',$booking->zone());$days=[];for($i=1;$i<=(int)$first->format('t');$i++)$days[]=$first->setDate((int)$first->format('Y'),(int)$first->format('m'),$i)->format('Y-m-d');
  return $this->render('admin/calendar.html.twig',['month'=>$month,'rows'=>$rows,'by_day'=>$byDay,'days'=>$days,'offset'=>(int)$first->format('N')-1,'previous'=>$first->modify('-1 month')->format('Y-m'),'next'=>$first->modify('+1 month')->format('Y-m'),'settings'=>$booking->settings(),'timezone'=>$booking->zone()->getName(),'error'=>$error]);
 }
}
