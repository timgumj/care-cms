<?php
namespace App\Service;
use App\Entity\Page;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\DBAL\Exception\TableNotFoundException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Twig\Environment;
final class StarterPacks
{
 public const CATALOG=[
  'practice'=>['name'=>'Practice','subtitle'=>'White & soft blue · Personal medical practice','brand'=>'Praxis Morgen','image'=>'team','color'=>'#dfeffa'],
  'atelier'=>['name'=>'Atelier','subtitle'=>'Navy & brass Â· Group practice','brand'=>'Praxis am Park','image'=>'team','color'=>'#243c57'],
  'clarity'=>['name'=>'Clarity','subtitle'=>'Blue & white Â· General practice','brand'=>'Praxis West','image'=>'doctor','color'=>'#203f73'],
  'linden'=>['name'=>'Linden','subtitle'=>'Sage & cream Â· Family practice','brand'=>'Praxis Linden','image'=>'reception','color'=>'#687b49'],
  'forma'=>['name'=>'Forma','subtitle'=>'Ink & stone Â· Independent practice','brand'=>'Praxis Mitte','image'=>'reception','color'=>'#333f3a'],
 ];
 public function __construct(private EntityManagerInterface $em,private Environment $twig,private MenuSettings $menus,#[Autowire('%env(DEFAULT_URI)%')] private string $baseUri){}
 public function all():array{try{return $this->em->getConnection()->fetchAllAssociative('SELECT * FROM starter_site ORDER BY created_at DESC');}catch(TableNotFoundException){return [];}}
 public function active():?array{foreach($this->all() as $site)if($site['active'])return $site;return null;}
 public function find(string $id):?array{foreach($this->all() as $site)if($site['id']===$id)return $site;return null;}
 public function siteForPage(Page $page):?array{if(preg_match('/^starter-([a-f0-9]{16})-/',$page->translationKey??'',$m)||preg_match('/(?:^| )starter-site-([a-f0-9]{16})(?: |$)/',$page->bodyClass,$m))return $this->find($m[1]);return null;}
 public function pages(string $id):array{
  $site=$this->find($id);if(!$site)throw new \DomainException('Starter site not found.');
  return $this->em->getRepository(Page::class)->createQueryBuilder('p')->where('p.translationKey LIKE :key')->andWhere('p.deletedAt IS NULL')->setParameter('key','starter-'.$id.'-%')->orderBy('p.path','ASC')->getQuery()->getResult();
 }
 public function create(string $key,string $name):array{
  if(!isset(self::CATALOG[$key]))throw new \DomainException('Unknown starter pack.');
  $name=trim($name);if(mb_strlen($name)<2||mb_strlen($name)>100||preg_match('/[\x00-\x1f\x7f]/',$name))throw new \DomainException('Enter a site name, 2â€“100 characters.');
  $id=bin2hex(random_bytes(8));$site=['id'=>$id,'pack_key'=>$key,'name'=>$name,'prefix'=>'/sites/'.$id.'/','active'=>0,'created_at'=>time()];
  $this->em->wrapInTransaction(function()use($site){
   $db=$this->em->getConnection();$db->executeStatement('UPDATE site_design SET id=id WHERE id=1');
   if((int)$db->fetchOne('SELECT COUNT(*) FROM starter_site')>=20)throw new \DomainException('This demo supports up to 20 starter sites.');
   $db->insert('starter_site',$site);
   foreach(['de-AT','en-AT'] as $locale)foreach(['home','about','services','contact','planner'] as $section){
    $page=$this->sample($site['pack_key'],$locale,$section,$site['name'],$site['prefix']);$page->translationKey='starter-'.$site['id'].'-'.$section;$page->bodyClass.=' starter-site-'.$site['id'];$page->published=false;$this->em->persist($page);
   }
  });
  return $site;
 }
 public function activate(string $id):void{
  $site=$this->find($id);if(!$site)throw new \DomainException('Starter site not found.');
  $this->em->wrapInTransaction(function()use($site,$id){
   $db=$this->em->getConnection();$db->executeStatement('UPDATE site_design SET id=id WHERE id=1');$pages=$this->pages($id);
   if(!in_array(count($pages),[8,10],true))throw new \DomainException('Restore all starter pages before publishing this site.');
   foreach($pages as $page){$page->published=true;$page->updatedAt=new \DateTimeImmutable();}
   $db->executeStatement('UPDATE starter_site SET active=0 WHERE active=1');$db->update('starter_site',['active'=>1],['id'=>$id]);
  });
 }
 public function deactivate():void{$this->em->getConnection()->executeStatement('UPDATE starter_site SET active=0 WHERE active=1');}
 public function sample(string $key,string $locale='en-AT',string $section='home',?string $name=null,string $prefix='/'):Page{
  if(!isset(self::CATALOG[$key])||!in_array($section,['home','about','services','contact','planner'],true))throw new \DomainException('Invalid starter template.');
  $en=str_starts_with($locale,'en');$page=new Page();$page->path=$prefix.($en?'en/':'').($section==='home'?'':$section.'/');$page->locale=$locale;$page->kind='Seite';$page->published=false;$page->bodyClass='pack-'.$key;$page->title=($section==='home'?($name??self::CATALOG[$key]['brand']):['about'=>$en?'About the practice':'Ãœber die Praxis','services'=>$en?'Services':'Leistungen','contact'=>$en?'Contact & hours':'Kontakt & Ã–ffnungszeiten','planner'=>$en?'Calendar & appointments':'Kalender & Termine'][$section]);$page->description=$en?'Fictional practice starter â€” edit this content for your own project.':'Fiktive Praxis-Vorlage â€” bearbeiten Sie diese Inhalte fÃ¼r Ihr Projekt.';$page->canonical=rtrim($this->baseUri,'/').$page->path;
  $data=['pack'=>self::CATALOG[$key],'key'=>$key,'english'=>$en,'brand'=>$name??self::CATALOG[$key]['brand'],'base'=>$prefix,'section'=>$section];
  $page->content=$this->twig->render('starters/'.($section==='home'?$key:($key==='practice'?'practice-inside':'inside')).'.html.twig',$data);$page->footerHtml=$this->twig->render('starters/footer.html.twig',$data);$page->headExtra='<link rel="stylesheet" href="/assets/starter-packs.css?v=14"><link rel="stylesheet" href="/theme/fonts.css">';return $page;
 }
 public function navigation(Page $page,array $site,array $languageLinks=[]):string{
  $en=str_starts_with($page->locale,'en');$prefix=$site['prefix'];$base=str_starts_with($page->path,$prefix)?$prefix:'/';
  $items=$this->menus->read()[$en?'en':'de'];
  foreach($items as &$item){
   $path=$item['url'];
   if(in_array($path,['/','/en/','/about/','/en/about/','/services/','/en/services/','/contact/','/en/contact/','/planner/','/en/planner/'],true))$item['url']=$base.ltrim($path,'/');
   if($item['button'])$item['url']='/booking/?lang='.($en?'en':'de');
   $item['current']=$item['url']===$page->path;
  }unset($item);
  if(!array_filter($items,fn($item)=>str_contains($item['url'],'/contact/')))$items[]=['url'=>$base.($en?'en/':'').'contact/','title'=>$en?'Contact':'Kontakt','button'=>false,'current'=>str_ends_with($page->path,'/contact/'),'new_tab'=>false];
  if(!array_filter($items,fn($item)=>str_contains($item['url'],'/planner/')))$items[]=['url'=>$base.($en?'en/':'').'planner/','title'=>$en?'Calendar':'Kalender','button'=>false,'current'=>str_ends_with($page->path,'/planner/'),'new_tab'=>false];
  $relative=substr($page->path,strlen($base));if(str_starts_with($relative,'en/'))$relative=substr($relative,3);
  $items=array_merge(array_values(array_filter($items,fn($item)=>!$item['button'])),array_values(array_filter($items,fn($item)=>$item['button'])));
  // Articles remain the independent CMS archive, not copied into every starter.
  return $this->twig->render('starters/navigation.html.twig',['key'=>$site['pack_key'],'brand'=>$site['name'],'english'=>$en,'base'=>$base,'items'=>$items,'language_links'=>$languageLinks?:['de'=>$base.$relative,'en'=>$base.'en/'.$relative]]);
 }
 public function contentPresentation(Page $source,array $languageLinks=[]):array{
  $site=$this->active();$english=str_starts_with($source->locale,'en');
  $home=$site?$this->em->getRepository(Page::class)->findOneBy(['path'=>$site['prefix'].($english?'en/':''),'published'=>true,'deletedAt'=>null]):null;
  $theme=$home?$this->present($home,$site):$this->sample('practice',$source->locale);
  $page=clone $source;foreach(['bodyClass','headExtra','footerHtml'] as $field)$page->$field=$theme->$field;
  $publicSite=$site??['pack_key'=>'practice','name'=>'Praxis Morgen','prefix'=>'/'];
  return ['page'=>$page,'header'=>$this->navigation($page,$publicSite,$languageLinks)];
 }
 public function bookingPresentation(bool $english):array{
  $site=$this->active();if(!$site){$page=$this->sample('practice',$english?'en-AT':'de-AT');$page->path='/booking/';$page->published=true;return ['page'=>$page,'header'=>$this->navigation($page,['pack_key'=>'practice','name'=>'Praxis Morgen','prefix'=>'/'])];}
  $home=$this->em->getRepository(Page::class)->findOneBy(['path'=>$site['prefix'].($english?'en/':''),'published'=>true,'deletedAt'=>null]);
  if(!$home)return [];$page=$this->present($home,$site);$page->path='/booking/';
  return ['page'=>$page,'header'=>$this->navigation($page,$site)];
 }
 public function present(Page $source,array $site):Page{
  $page=clone $source;$prefix=$site['prefix'];if(!str_starts_with($source->path,$prefix))return $page;$page->path='/'.substr($source->path,strlen($prefix));$page->canonical=rtrim($this->baseUri,'/').$page->path;
  foreach(['content','headerHtml','footerHtml'] as $field)$page->$field=str_replace($prefix,'/',$page->$field);return $page;
 }
}
