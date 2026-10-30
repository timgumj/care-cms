<?php
namespace App\Controller;
use App\Entity\Page;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{Request,Response};
use Symfony\Component\Routing\Attribute\Route;
final class ArticleArchiveController extends AbstractController
{
    #[Route('/artikel/', name:'site_articles_de', methods:['GET'])]
    #[Route('/en/articles/', name:'site_articles_en', methods:['GET'])]
    public function index(Request $request, EntityManagerInterface $em, \App\Service\SiteNavigation $navigation, \App\Service\StarterPacks $packs): Response {
        $english=$request->getPathInfo()==='/en/articles/';$locale=$english?'en-AT':'de-AT';$request->setLocale($english?'en':'de');
        $repository=$em->getRepository(Page::class);
        $home=$packs->sample('practice',$locale);
        $posts=$repository->findBy(['kind'=>'Beitrag','locale'=>$locale,'published'=>true,'deletedAt'=>null],['updatedAt'=>'DESC']);
        $category=$request->query->getString('category');if(in_array($category,['practice','planning'],true))$posts=array_values(array_filter($posts,fn($post)=>(preg_match('/preparing-first-visit|appointment-planning|follow-up-planning|finding-your-way/',$post->translationKey??'')?'planning':'practice')===$category));
        $page=clone $home;$page->title=$english?'Articles':'Artikel';$page->description=$english?'Articles from Care Studio’s practice.':'Artikel aus der Ordination Care Studio.';$page->canonical=$request->getSchemeAndHttpHost().$request->getPathInfo();$page->schema=[];$page->kind='Seite';
        $page->path=$request->getPathInfo();$presentation=$packs->contentPresentation($page,['de'=>'/artikel/','en'=>'/en/articles/']);$page=$presentation['page'];$header=$presentation['header'];
        $response=$this->render('site/page.html.twig',['page'=>$page,'header'=>$header,'alternates'=>[],'content'=>$this->renderView('site/articles.html.twig',['posts'=>$posts,'english'=>$english,'page'=>$page])]);
        $response->headers->set('Content-Language',$locale);
        if(!$posts)$response->headers->set('X-Robots-Tag','noindex, follow');
        return $response;
    }
}
