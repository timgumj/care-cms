<?php
namespace App\Controller\Admin;
use App\Security\AdminArea;
use App\Service\AnnouncementSettings;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{Request,Response};
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
#[IsGranted('ROLE_ADMIN')]
final class AnnouncementController extends AbstractController
{
    #[Route(AdminArea::PATH.'/announcements',name:'admin_announcements',methods:['GET','POST'])]
    public function settings(Request $request,AnnouncementSettings $settings): Response {
        $values=$settings->read();$error=null;
        if($request->isMethod('POST')){
            if(!$this->isCsrfTokenValid('announcements',$request->request->get('_csrf_token')))throw $this->createAccessDeniedException();
            $posted=$request->request->all();$values=[];
            foreach(['de','en'] as $locale){
                $values[$locale]=['enabled'=>isset($posted[$locale.'_enabled'])];
                foreach(['title','text','link_label','mode','target','popup_text'] as $key)$values[$locale][$key]=is_string($posted[$locale.'_'.$key]??null)?trim($posted[$locale.'_'.$key]):'';
            }
            if(!$settings->valid($values))$error='Check the announcement fields. Use an anchor such as #weihnachtsurlaub, a local /path/ or an HTTPS link. Popup mode needs notification text.';
            else {
                try{$settings->save($values);$this->addFlash('announcement_saved','Announcement settings saved.');return $this->redirectToRoute('admin_announcements',['_locale'=>$request->getLocale()]);}
                catch(\RuntimeException|\Symfony\Component\Filesystem\Exception\IOExceptionInterface $exception){$error='The announcement could not be saved. Please check the private directory permissions.';}
            }
        }
        return $this->render('admin/announcements.html.twig',['settings'=>$values,'error'=>$error]);
    }
}
