<?php
namespace App\Controller\Admin;
use App\Entity\Page;
use App\Service\StarterPacks;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{Request,Response};
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
#[IsGranted('ROLE_EDITOR')]
final class DraftPreviewController extends AbstractController
{
 #[Route('/manage/starter-pages/{id}',name:'admin_starter_page_preview',requirements:['id'=>'[1-9][0-9]*'],methods:['GET'])]
 public function index(int $id,Request $request,EntityManagerInterface $em,StarterPacks $packs):Response{
  $page=$em->find(Page::class,$id);if(!$page||$page->deletedAt||!($site=$packs->siteForPage($page)))throw $this->createNotFoundException();
  $request->setLocale(str_starts_with($page->locale,'en')?'en':'de');
  return $this->render('site/page.html.twig',['page'=>$page,'content'=>$page->content,'header'=>$packs->navigation($page,$site),'alternates'=>[]],new Response(headers:['Cache-Control'=>'private, no-store','X-Robots-Tag'=>'noindex, nofollow']));
 }
}
