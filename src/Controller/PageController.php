<?php
namespace App\Controller;
use App\Entity\Page;
use App\Service\PageTranslations;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{Request,Response};
use Symfony\Component\Routing\Attribute\Route;
class PageController extends AbstractController
{
    #[Route('/{slug}', name: 'site_page', requirements: ['slug'=>'(?!admin(?:/|$)|wp-content/|wp-includes/|_app/|optimized-images/|assets/|uploads/|demo-media/).*'], defaults: ['slug'=>''], methods: ['GET','HEAD'], priority: -100)]
    public function show(Request $request, EntityManagerInterface $em, PageTranslations $translations, \App\Service\HomeAnnouncement $announcement, \App\Service\SiteNavigation $navigation, \App\Service\StarterPacks $packs): Response {
        $path=$request->getPathInfo();
        $active=$packs->active();$alias=false;$page=null;$defaultHeader=null;
        // First-load presentation uses a real starter even before starter setup has run.
        if(!$active&&preg_match('~^/(en/)?(?:(about|services|contact|planner|privacy|terms|cookies|offers)/)?$~',$path,$match)){
            $english=!empty($match[1]);$page=$packs->sample('practice',$english?'en-AT':'de-AT',$match[2]??'home');$page->published=true;
            $defaultHeader=$packs->navigation($page,['pack_key'=>'practice','name'=>'Praxis Morgen','prefix'=>'/']);
        }
        if($active&&!str_starts_with($path,'/sites/')){$page=$em->getRepository(Page::class)->findOneBy(['path'=>$active['prefix'].ltrim($path,'/'),'published'=>true,'deletedAt'=>null]);$alias=$page!==null;}
        $page??=$em->getRepository(Page::class)->findOneBy(['path'=>$path,'published'=>true,'deletedAt'=>null]);
        if($alias)$page=$packs->present($page,$active);
        if(!$page&&str_starts_with($path,'/sites/')&&$request->attributes->get('_admin_session_active')&&$this->isGranted('ROLE_EDITOR')){
            $draft=$em->getRepository(Page::class)->findOneBy(['path'=>$path,'deletedAt'=>null]);if($draft&&$packs->siteForPage($draft))$page=$draft;
        }
        if(!$page && $path!=='/' && !str_ends_with($path,'/') && $em->getRepository(Page::class)->findOneBy(['path'=>$path.'/','published'=>true,'deletedAt'=>null]))return $this->redirect($path.'/',301);
        if(!$page)throw $this->createNotFoundException();
        $request->setLocale(str_starts_with($page->locale,'en')?'en':'de');
        $alternates=$translations->forPage($page);if($alias)foreach($alternates as $language=>$translation)$alternates[$language]=$packs->present($translation,$active);
        if($page->kind==='Beitrag'||in_array(trim(str_replace('/en/','/',$path),'/'),['privacy','terms','cookies','offers'],true)){
            $languageLinks=[];foreach($alternates as $language=>$translation)$languageLinks[str_starts_with($language,'en')?'en':'de']=$translation->path;
            $presentation=$packs->contentPresentation($page,$languageLinks);$page=$presentation['page'];$defaultHeader=$presentation['header'];
        }
        $content=$announcement->decorate($page);if($page->kind==='Beitrag')$content=preg_replace('~^\s*<h1\b[^>]*>.*?</h1>~is','',$content,1);
        $response=$this->render('site/page.html.twig',['page'=>$page,'content'=>$content,'related_posts'=>array_slice(array_filter($em->getRepository(Page::class)->findBy(['kind'=>'Beitrag','locale'=>$page->locale,'published'=>true,'deletedAt'=>null],['updatedAt'=>'DESC']),fn($post)=>$post->id!==$page->id),0,3),'alternates'=>$alternates,'header'=>$defaultHeader??$navigation->header($page,$translations->headerForPage($page))]);
        $response->headers->set('Content-Language',$page->locale);
        if(!$page->published){$response->headers->set('X-Robots-Tag','noindex, nofollow');$response->headers->set('Cache-Control','private, no-store');}
        if($this->getParameter('kernel.environment')!=='prod')$response->headers->set('X-Robots-Tag','noindex, nofollow');
        return $response;
    }
    #[Route('/sitemap.xml', name: 'site_sitemap', methods: ['GET'])]
    public function sitemap(EntityManagerInterface $em, PageTranslations $translations, \App\Service\StarterPacks $packs): Response {
        $dom=new \DOMDocument('1.0','UTF-8');$root=$dom->createElementNS('http://www.sitemaps.org/schemas/sitemap/0.9','urlset');$root->setAttributeNS('http://www.w3.org/2000/xmlns/','xmlns:xhtml','http://www.w3.org/1999/xhtml');$dom->appendChild($root);
        $active=$packs->active();
        foreach($em->getRepository(Page::class)->findBy(['published'=>true,'deletedAt'=>null],['path'=>'ASC']) as $page){
            if($active){if(str_starts_with($page->translationKey??'','starter-'.$active['id'].'-'))$page=$packs->present($page,$active);elseif(($packs->siteForPage($page)['id']??null)!==$active['id']&&$page->kind!=='Beitrag'&&!str_starts_with($page->translationKey??'','demo-'))continue;}
            $url=$dom->createElement('url');$loc=$dom->createElement('loc');$loc->appendChild($dom->createTextNode($page->canonical));$url->appendChild($loc);
            $alternates=$translations->forPage($page);if($active&&str_starts_with($page->translationKey??'','starter-'.$active['id'].'-'))foreach($alternates as $locale=>$alternate)$alternates[$locale]=$packs->present($alternate,$active);
            if(count($alternates)>1)foreach($alternates as $locale=>$alternate){$link=$dom->createElementNS('http://www.w3.org/1999/xhtml','xhtml:link');$link->setAttribute('rel','alternate');$link->setAttribute('hreflang',$locale);$link->setAttribute('href',$alternate->canonical);$url->appendChild($link);}
            $root->appendChild($url);
        }
        return new Response($dom->saveXML(),200,['Content-Type'=>'application/xml; charset=UTF-8']);
    }
    #[Route('/wp-sitemap.xml', name: 'site_legacy_wp_sitemap', methods: ['GET'])]
    #[Route('/sitemap_index.xml', name: 'site_legacy_sitemap_index', methods: ['GET'])]
    public function legacySitemap(): Response { return $this->redirectToRoute('site_sitemap',[],301); }
    #[Route('/robots.txt', name: 'site_robots', methods: ['GET'])]
    public function robots(#[\Symfony\Component\DependencyInjection\Attribute\Autowire('%env(DEFAULT_URI)%')] string $baseUri): Response {
        return new Response($this->getParameter('kernel.environment')==='prod' ? "User-agent: *\nAllow: /\nSitemap: ".rtrim($baseUri,'/')."/sitemap.xml\n" : "User-agent: *\nDisallow: /\n",200,['Content-Type'=>'text/plain']);
    }
}
