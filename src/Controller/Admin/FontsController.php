<?php
namespace App\Controller\Admin;
use App\Service\FontLibrary;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{Request,Response};
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
#[IsGranted('ROLE_ADMIN')]
final class FontsController extends AbstractController
{
 #[Route('/manage/fonts',name:'admin_fonts',methods:['GET','POST'])]
 public function index(Request $request,FontLibrary $fonts):Response{
  $error=null;
  if($request->isMethod('POST')){
   if(!$this->isCsrfTokenValid('fonts',$request->request->getString('_token')))throw $this->createAccessDeniedException();
   try{
    if($request->request->getString('action')==='upload'){
     if($request->request->getString('license')!=='yes')throw new \DomainException('Confirm you have permission to use and redistribute this font.');
     $file=$request->files->get('font');if(!$file instanceof UploadedFile||!$file->isValid()||strtolower($file->getClientOriginalExtension())!=='woff2'||$file->getSize()>2097152)throw new \DomainException('Choose a valid WOFF2 upload, maximum 2 MB.');
     $fonts->import(file_get_contents($file->getPathname()),$request->request->getString('label'));
    }elseif($request->request->getString('action')==='select')$fonts->select($request->request->getString('body'),$request->request->getString('heading'));
    else throw new \DomainException('Invalid font action.');
    $this->addFlash('success','Font settings saved.');return $this->redirectToRoute('admin_fonts');
   }catch(\DomainException $e){$error=$e->getMessage();}
  }
  return $this->render('admin/fonts.html.twig',['fonts'=>$fonts->all(),'builtins'=>FontLibrary::BUILTINS,'selected'=>$fonts->selected(),'error'=>$error]);
 }
}
