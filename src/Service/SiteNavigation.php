<?php
namespace App\Service;
use App\Entity\Page;
use Doctrine\ORM\EntityManagerInterface;
use Twig\Environment;
final class SiteNavigation
{
    public function __construct(private MenuSettings $settings,private EntityManagerInterface $em,private Environment $twig,private PageTranslations $translations,private StarterPacks $packs) {}
    public function items(string $locale,string $currentPath): array {
        $items=[];$language=$locale==='en'?'en-AT':'de-AT';
        foreach($this->settings->read()[$locale] as $item){
            if($item['type']==='page'&&!$this->em->getRepository(Page::class)->findOneBy(['path'=>$item['url'],'locale'=>$language,'published'=>true,'deletedAt'=>null]))continue;
            $item['current']=$item['url']===$currentPath;$items[]=$item;
        }
        return $items;
    }
    public function header(Page $page,string $header,array $languageLinks=[]): string {
        if($site=$this->packs->siteForPage($page)){
            if(!$languageLinks)foreach($this->translations->forPage($page) as $language=>$translation)$languageLinks[str_starts_with($language,'en')?'en':'de']=str_starts_with($page->path,$site['prefix'])?$translation->path:str_replace($site['prefix'],'/',$translation->path);
            return $this->packs->navigation($page,$site,$languageLinks);
        }
        $locale=str_starts_with($page->locale,'en')?'en':'de';
        if(!$languageLinks)foreach($this->translations->forPage($page) as $language=>$translation)$languageLinks[str_starts_with($language,'en')?'en':'de']=$translation->path;
        if(trim($header)==='')$header=$this->twig->render('site/'.$locale.'/header.html.twig');
        $newHeader=$this->twig->render('site/_navigation.html.twig',['english'=>$locale==='en','items'=>$this->items($locale,$page->path),'language_links'=>$languageLinks,'current_locale'=>$locale]);
        return preg_match('~<header\b[^>]*>.*?</header>~s',$header)?preg_replace_callback('~<header\b[^>]*>.*?</header>~s',fn()=>$newHeader,$header,1):$newHeader.$header;
    }
}
