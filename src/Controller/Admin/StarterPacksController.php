<?php
namespace App\Controller\Admin;
use App\Service\StarterPacks;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{Request,Response};
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
#[IsGranted('ROLE_ADMIN')]
final class StarterPacksController extends AbstractController
{
 #[Route('/manage/starters',name:'admin_starters',methods:['GET','POST'])]
 public function index(Request $request,StarterPacks $packs):Response{
  $error=null;
  if($request->isMethod('POST')){
   if(!$this->isCsrfTokenValid('starter_packs',$request->request->getString('_token')))throw $this->createAccessDeniedException();
   try{
    if($request->request->getString('action')==='create'){$site=$packs->create($request->request->getString('pack'),$request->request->getString('name'));return $this->redirectToRoute('admin_starter_site',['id'=>$site['id']]);}
    if($request->request->getString('action')==='activate'){
     if($request->request->getString('confirm')!=='yes')throw new \DomainException('Confirm publication before switching the website.');
     $packs->activate($request->request->getString('id'));
    }elseif($request->request->getString('action')==='deactivate'){
     if($request->request->getString('confirm')!=='yes')throw new \DomainException('Confirm returning to the original demo.');$packs->deactivate();
    }else throw new \DomainException('Invalid starter action.');
    return $this->redirectToRoute('admin_starters');
   }catch(\DomainException $e){$error=$e->getMessage();}
  }
  return $this->render('admin/starters.html.twig',['packs'=>StarterPacks::CATALOG,'sites'=>$packs->all(),'error'=>$error]);
 }
 #[Route('/manage/starters/site/{id}',name:'admin_starter_site',requirements:['id'=>'[a-f0-9]{16}'],methods:['GET'])]
 public function site(string $id,StarterPacks $packs):Response{
  $site=$packs->find($id);if(!$site)throw $this->createNotFoundException();
  return $this->render('admin/starter_site.html.twig',['site'=>$site,'pages'=>$packs->pages($id)]);
 }
 #[Route('/manage/starters/preview/{key}',name:'admin_starter_preview',requirements:['key'=>'practice|atelier|clarity|linden|forma'],methods:['GET'])]
 public function preview(string $key,Request $request,StarterPacks $packs):Response{
  $en=$request->query->get('lang','en')==='en';$request->setLocale($en?'en':'de');$page=$packs->sample($key,$en?'en-AT':'de-AT');
  $header=$packs->navigation($page,['pack_key'=>$key,'name'=>StarterPacks::CATALOG[$key]['brand'],'prefix'=>'/']);
  return $this->render('site/page.html.twig',['page'=>$page,'content'=>$page->content,'header'=>$header,'alternates'=>[]],new Response(headers:['Cache-Control'=>'private, no-store','X-Robots-Tag'=>'noindex, nofollow']));
 }
}
