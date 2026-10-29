<?php
namespace App\Service;
use App\Entity\Page;
use Doctrine\ORM\EntityManagerInterface;
class PageTranslations
{
    public function __construct(private EntityManagerInterface $em) {}
    /** @return array<string, Page> Published, explicitly linked translations only. */
    public function forPage(Page $page): array {
        $pages=$page->translationKey ? $this->em->getRepository(Page::class)->findBy(['translationKey'=>$page->translationKey,'published'=>true,'deletedAt'=>null],['locale'=>'ASC']) : [$page];
        $links=[];foreach($pages as $translation)$links[$translation->locale]=$translation;
        return $links;
    }
    public function headerForPage(Page $page): string {
        $links=[];
        foreach($this->forPage($page) as $locale=>$translation){
            $short=str_starts_with($locale,'en')?'en':'de';
            $links[]='<a href="'.htmlspecialchars($translation->path,ENT_QUOTES).'" lang="'.$short.'" hreflang="'.$locale.'"'.($locale===$page->locale?' aria-current="page"':'').'>'.strtoupper($short).'</a>';
        }
        $nav='<nav aria-label="'.(str_starts_with($page->locale,'en')?'Language':'Sprachauswahl').'" class="language-switcher">'.implode(' <span aria-hidden="true">/</span> ',$links).'</nav>';
        return preg_replace('~<nav\b[^>]*class="[^"]*\blanguage-switcher\b[^"]*"[^>]*>.*?</nav>~s',$nav,$page->headerHtml);
    }
}
