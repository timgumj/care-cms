<?php
namespace App\Service;
use App\Entity\Page;
use Twig\Environment;
final class HomeAnnouncement
{
    public function __construct(private AnnouncementSettings $settings,private Environment $twig) {}
    public function decorate(Page $page): string {
        if(!in_array($page->path,['/','/en/'],true))return $page->content;
        $locale=str_starts_with($page->locale,'en')?'en':'de';$item=$this->settings->read()[$locale];
        if(!$item['enabled'])return $page->content;
        $document=new \DOMDocument('1.0','UTF-8');$previous=libxml_use_internal_errors(true);
        try{$document->loadHTML('<?xml encoding="UTF-8"><div id="announcement-source">'.$page->content.'</div>',LIBXML_HTML_NOIMPLIED|LIBXML_HTML_NODEFDTD);}finally{libxml_clear_errors();libxml_use_internal_errors($previous);}
        $xpath=new \DOMXPath($document);$root=$xpath->query('//*[@id="announcement-source"]')->item(0);
        $hero=$xpath->query('//*[contains(concat(" ",normalize-space(@class)," ")," hero ")]')->item(0);
        if(!$root||!$hero)return $page->content;
        if($item['mode']==='anchor'){
            $target=substr($item['target'],1);$found=false;
            foreach($xpath->query('//*[@id]') as $element)if($element->getAttribute('id')===$target){$found=true;break;}
            if(!$found&&$target==='weihnachtsurlaub'){
                $holiday=$xpath->query('//*[contains(concat(" ",normalize-space(@class)," ")," home-holiday-notice ")]')->item(0);
                if($holiday){$holiday->setAttribute('id',$target);$found=true;}
            }
            // An anchor without a matching section must not produce a dead notice.
            if(!$found)return $page->content;
        }
        $hero->setAttribute('class',$hero->getAttribute('class').' announcement-hero');
        $fragment=new \DOMDocument('1.0','UTF-8');$previous=libxml_use_internal_errors(true);
        try{$fragment->loadHTML('<?xml encoding="UTF-8"><div id="announcement-fragment">'.$this->twig->render('site/_announcement.html.twig',['notice'=>$item,'english'=>$locale==='en']).'</div>',LIBXML_HTML_NOIMPLIED|LIBXML_HTML_NODEFDTD);}finally{libxml_clear_errors();libxml_use_internal_errors($previous);}
        $fragmentRoot=(new \DOMXPath($fragment))->query('//*[@id="announcement-fragment"]')->item(0);
        foreach(iterator_to_array($fragmentRoot->childNodes) as $child)$hero->appendChild($document->importNode($child,true));
        $html='';foreach($root->childNodes as $child)$html.=$document->saveHTML($child);return $html;
    }
}
