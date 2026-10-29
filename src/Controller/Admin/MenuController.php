<?php
namespace App\Controller\Admin;
use App\Entity\Page;
use App\Security\AdminArea;
use App\Service\MenuSettings;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{Request,Response};
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
#[IsGranted('ROLE_ADMIN')]
final class MenuController extends AbstractController
{
    #[Route(AdminArea::PATH.'/menus',name:'admin_menus',methods:['GET','POST'])]
    public function index(Request $request,MenuSettings $settings,EntityManagerInterface $em): Response {
        $locale=$request->query->get('menu_locale','de');if(!in_array($locale,['de','en'],true))throw $this->createNotFoundException();
        $items=$settings->read()[$locale];$error=null;
        $pages=$em->getRepository(Page::class)->findBy(['locale'=>$locale==='de'?'de-AT':'en-AT','deletedAt'=>null],['kind'=>'ASC','title'=>'ASC']);
        $paths=array_map(fn(Page $page)=>$page->path,$pages);
        if($request->isMethod('POST')){
            if(!$this->isCsrfTokenValid('menus_'.$locale,$request->request->get('_csrf_token')))throw $this->createAccessDeniedException();
            $posted=$request->request->all();$rows=$posted['items']??[];$items=[];
            if(!is_array($rows)||count($rows)>30)$error='A menu can contain at most 30 items.';
            else foreach($rows as $row){
                if(!is_array($row)){$error='Please check the menu labels and destinations.';break;}
                $type=is_string($row['type']??null)?$row['type']:'';
                $target=$row[$type==='page'?'page_path':'custom_url']??'';
                $items[]=['title'=>is_string($row['title']??null)?trim($row['title']):'','type'=>$type,'url'=>is_string($target)?trim($target):'','new_tab'=>isset($row['new_tab']),'button'=>isset($row['button'])];
            }
            if(!$error&&(!$settings->valid($items)||array_filter($items,fn($item)=>$item['type']==='page'&&!in_array($item['url'],$paths,true))))$error='Please check the menu labels and destinations.';
            if(!$error){
                try{$settings->saveLocale($locale,$items);$this->addFlash('menu_saved','Menu saved.');return $this->redirectToRoute('admin_menus',['menu_locale'=>$locale,'_locale'=>$request->getLocale()]);}
                catch(\RuntimeException|\Symfony\Component\Filesystem\Exception\IOExceptionInterface $exception){$error='The menu could not be saved. Please check the private directory permissions.';}
            }
        }
        return $this->render('admin/menus.html.twig',['items'=>$items,'pages'=>$pages,'menu_locale'=>$locale,'error'=>$error]);
    }
}
