<?php
namespace App\Service;
use App\Entity\Page;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\String\Slugger\AsciiSlugger;

final class ContentLifecycle
{
    public function __construct(private EntityManagerInterface $em, #[\Symfony\Component\DependencyInjection\Attribute\Autowire('%env(DEFAULT_URI)%')] private string $baseUri,private ?StarterPacks $packs=null) {}
    public function preparePost(Page $post): void
    {
        $post->kind='Beitrag';
        if ($post->path==='/' || $post->path==='' || str_starts_with($post->path,'/artikel/entwurf-')) {
            $slug=substr((new AsciiSlugger())->slug($post->title)->lower()->toString(),0,200) ?: 'artikel';
            $prefix=$post->locale==='en-AT'?'/en/articles/':'/artikel/';
            $post->path=$this->availablePath($prefix.$slug.'/');
        }
        $post->canonical=rtrim($this->baseUri,'/').$post->path;
        $post->translationKey ??= 'article-'.bin2hex(random_bytes(12));
        $this->applyTheme($post);
    }
    public function applyTheme(Page $post): void
    {
        $prefix=$this->packs?->active()['prefix']??'/';
        $home=$this->em->getRepository(Page::class)->findOneBy(['path'=>$prefix.($post->locale==='en-AT'?'en/':'')]);
        if($home&&($site=$this->packs?->active()))$home=$this->packs->present($home,$site);
        if ($home) foreach(['headExtra','headerHtml','footerHtml','bodyClass'] as $field) $post->$field=$home->$field;
        $post->schema=[];
    }
    public function englishDraft(Page $source): Page
    {
        if($source->deletedAt || $source->locale!=='de-AT' || $source->kind!=='Beitrag') throw new \DomainException('Only active German articles can be duplicated.');
        if ($source->translationKey) {
            $existing=$this->em->getRepository(Page::class)->findOneBy(['translationKey'=>$source->translationKey,'locale'=>'en-AT']);
            if($existing) throw new \DomainException('An English version already exists, possibly in Trash.');
        }
        $source->translationKey ??= 'article-'.bin2hex(random_bytes(12));
        $draft=clone $source;$draft->id=null;$draft->locale='en-AT';$draft->published=false;$draft->updatedAt=new \DateTimeImmutable();
        $draft->path=$this->availablePath('/en/articles/'.basename(trim($source->path,'/')).'/');
        $this->preparePost($draft);
        $this->em->persist($draft);$this->em->flush();
        return $draft;
    }
    public function trash(Page $page): void
    {
        if($page->deletedAt) return;
        $page->deletedAt=new \DateTimeImmutable();$page->published=false;$this->em->flush();
    }
    public function restore(Page $page): void
    {
        if(!$page->deletedAt) throw new \DomainException('Content is not in Trash.');
        $page->deletedAt=null;$page->published=false;$page->updatedAt=new \DateTimeImmutable();$this->em->flush();
    }
    public function permanentlyDelete(Page $page): void
    {
        if(!$page->deletedAt) throw new \DomainException('Only trashed content can be permanently deleted.');
        $this->em->remove($page);$this->em->flush();
    }
    private function availablePath(string $path): string
    {
        $base=rtrim($path,'/');$candidate=$path;$number=2;
        while($this->em->getRepository(Page::class)->findOneBy(['path'=>$candidate])) $candidate=$base.'-'.$number++.'/';
        return $candidate;
    }
}
