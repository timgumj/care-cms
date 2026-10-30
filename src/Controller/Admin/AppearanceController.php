<?php
namespace App\Controller\Admin;
use App\Service\AppearanceSettings;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{Request,Response};
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
#[IsGranted('ROLE_ADMIN')]
final class AppearanceController extends AbstractController {
 #[Route('/manage/appearance',name:'admin_appearance',methods:['GET','POST'])]
 public function index(Request $request,AppearanceSettings $settings):Response {
  if($request->isMethod('POST')){if(!$this->isCsrfTokenValid('appearance',$request->request->getString('_token')))throw $this->createAccessDeniedException();$mode=$request->request->getString('desktop_menu');if(!in_array($mode,['inline','hamburger'],true))throw $this->createNotFoundException();$settings->save($mode);$this->addFlash('success','Navigation style saved.');return $this->redirectToRoute('admin_appearance');}
  return $this->render('admin/appearance.html.twig',['mode'=>$settings->mode()]);
 }
}
