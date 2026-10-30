<?php
namespace App\Controller;
use App\Entity\Page;
use App\Service\StarterPacks;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{Request,Response};
use Symfony\Component\Routing\Attribute\Route;
final class SearchController extends AbstractController {
 #[Route('/search/',name:'site_search',methods:['GET'])]
 public function index(Request $request,EntityManagerInterface $em,StarterPacks $packs):Response {
  $english=$request->query->getString('lang')==='en';$locale=$english?'en-AT':'de-AT';$request->setLocale($english?'en':'de');$q=mb_substr(trim($request->query->getString('q')),0,100);$posts=[];
  if(mb_strlen($q)>=2){$needle='%'.str_replace(['!','%','_'],['!!','!%','!_'],$q).'%';$posts=$em->getConnection()->fetchFirstColumn("SELECT id FROM Page WHERE published=1 AND deletedAt IS NULL AND locale=? AND (title LIKE ? ESCAPE '!' OR description LIKE ? ESCAPE '!') ORDER BY updatedAt DESC LIMIT 30",[$locale,$needle,$needle]);$posts=$posts?$em->getRepository(Page::class)->findBy(['id'=>$posts]):[];
   $active=$packs->active();$posts=array_values(array_filter($posts,fn($p)=>!$packs->siteForPage($p)||($active&&$packs->siteForPage($p)['id']===$active['id'])));if($active)foreach($posts as &$p)if($packs->siteForPage($p))$p=$packs->present($p,$active);unset($p);
  }
  $source=$packs->sample('practice',$locale);$source->path='/search/';$source->title=$english?'Search':'Suche';$presentation=$packs->contentPresentation($source);$page=$presentation['page'];$content=$this->renderView('site/search.html.twig',['q'=>$q,'posts'=>$posts,'english'=>$english]);return $this->render('site/page.html.twig',['page'=>$page,'header'=>$presentation['header'],'alternates'=>[],'content'=>$content],new Response('',200,['X-Robots-Tag'=>'noindex, follow']));
 }
}
