<?php
require __DIR__.'/../vendor/autoload.php';
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\{EntityManager,ORMSetup};
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use App\Service\{StarterPacks,MenuSettings,FontLibrary};
function starterCheck(bool $v,string $why):void{if(!$v)throw new RuntimeException($why);}
function starterReject(callable $fn):void{try{$fn();throw new LogicException('Unsafe input accepted');}catch(DomainException){}}
$db=DriverManager::getConnection(['driver'=>'pdo_sqlite','memory'=>true]);
foreach(glob(__DIR__.'/../migrations/*.php') as $path){require_once $path;$class='DoctrineMigrations\\'.basename($path,'.php');$before=$db->createSchemaManager()->introspectSchema();$after=clone $before;$m=new $class($db,new Psr\Log\NullLogger());$m->up($after);foreach($m->getSql() as $q)$db->executeStatement($q->getStatement(),$q->getParameters(),$q->getTypes());foreach($db->getDatabasePlatform()->getAlterSchemaSQL($db->createSchemaManager()->createComparator()->compareSchemas($before,$after)) as $sql)$db->executeStatement($sql);}
$config=ORMSetup::createAttributeMetadataConfiguration([__DIR__.'/../src/Entity'],true,null,new ArrayAdapter());$em=new EntityManager($db,$config);
$root=dirname(__DIR__);$temporary=$root.'/var/starter-test-'.bin2hex(random_bytes(6));mkdir($temporary,0700,true);
$templates=[];foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root.'/templates')) as $f)if($f->isFile()&&str_ends_with($f->getFilename(),'.twig'))$templates[str_replace('\\','/',substr($f->getPathname(),strlen($root.'/templates/')))]=file_get_contents($f->getPathname());
$twig=new Twig\Environment(new Twig\Loader\ArrayLoader($templates),['strict_variables'=>true]);$twig->addFunction(new Twig\TwigFunction('path',fn($name,$args=[])=>'/manage/'.$name));$twig->addFunction(new Twig\TwigFunction('csrf_token',fn()=>''));$twig->addFilter(new Twig\TwigFilter('trans',fn($s)=>$s));
$packs=new StarterPacks($em,$twig,new MenuSettings($temporary),'https://demo.example.test');$original=new App\Entity\Page();$original->path='/';$original->title='Original demo';$original->content='<main>Original</main>';$original->canonical='https://demo.example.test/';$em->persist($original);$em->flush();$originalId=$original->id;
try{
 starterReject(fn()=>$packs->create('../unsafe','Demo'));
 $sites=[];$sanitizer=new App\Security\EditorContentSanitizer();
 foreach(array_keys(StarterPacks::CATALOG) as $key){
  foreach(['de-AT','en-AT'] as $locale){$page=$packs->sample($key,$locale);starterCheck(str_contains($page->content,'/booking/')&&str_contains($page->content,'/assets/starters/'),'Starter booking/images missing');$safe=$sanitizer->sanitize($page->content);starterCheck(str_contains($safe,'pack-'.$key)&&str_contains($safe,'<main'),'Visual editor strips starter layout');}
  foreach(['about','services','contact','planner'] as $section){$inner=$packs->sample($key,'en-AT',$section);starterCheck(str_contains($inner->content,'pack-'.$key),'Inner page lost starter styling');if($key==='practice')starterCheck(str_contains($inner->content,'<img'),'Practice inner page lacks photography');}
  $site=$packs->create($key,'Demo '.$key);$sites[]=$site;$pages=$packs->pages($site['id']);starterCheck(count($pages)===10,'Wrong page count');foreach($pages as $page)starterCheck(!$page->published&&$page->deletedAt===null,'Starter published without confirmation');
 }
 starterCheck($em->find(App\Entity\Page::class,$originalId)->content==='<main>Original</main>','Original overwritten');
 $packs->activate($sites[0]['id']);starterCheck($packs->active()['id']===$sites[0]['id'],'Activation failed');
 foreach($packs->pages($sites[0]['id']) as $page){starterCheck($page->published,'Pack partly published');$presented=$packs->present($page,$sites[0]);starterCheck(!str_starts_with($presented->path,'/sites/')&&!str_contains($presented->content,$sites[0]['prefix']),'Alias URLs not normalised');}
 $home=$em->getRepository(App\Entity\Page::class)->findOneBy(['path'=>$sites[0]['prefix'].'en/']);
 $navService=new App\Service\SiteNavigation(new MenuSettings($temporary),$em,$twig,new App\Service\PageTranslations($em),$packs);
 $navigation=$navService->header($packs->present($home,$sites[0]),'');starterCheck(!str_contains($navigation,$sites[0]['prefix'])&&str_contains($navigation,'/en/about/'),'Active navigation aliases incorrect');
 $article=new App\Entity\Page();$article->title='A new article';(new App\Service\ContentLifecycle($em,'https://demo.example.test',$packs))->preparePost($article);starterCheck(str_contains($article->bodyClass,'starter-site-'.$sites[0]['id'])&&!str_contains($article->footerHtml,$sites[0]['prefix']),'New articles lose active theme');
 $api=new App\Controller\ContentApiController();$api->setContainer(new Symfony\Component\DependencyInjection\Container());$response=$api->pages(Symfony\Component\HttpFoundation\Request::create('/api/v1/pages?locale=en-AT'),$em,new App\Security\EditorContentSanitizer(),'',$packs);$data=json_decode($response->getContent(),true,512,JSON_THROW_ON_ERROR);starterCheck(count($data['items'])===5&&in_array('/',array_column($data['items'],'path'),true)===false&&in_array('/en/',array_column($data['items'],'path'),true),'Active API scope/aliases incorrect');
 $packs->activate($sites[1]['id']);starterCheck((int)$db->fetchOne('SELECT COUNT(*) FROM starter_site WHERE active=1')===1,'More than one active starter');
 $packs->deactivate();starterCheck($packs->active()===null&&$em->find(App\Entity\Page::class,$originalId)->published,'Restore destroyed original');
 $malicious=$packs->sample('linden','en-AT','home','<script>alert(1)</script>');starterCheck(!str_contains($malicious->content,'<script>alert(1)</script>'),'Practice name not escaped');
 foreach(array_keys(StarterPacks::CATALOG) as $key){$planner=$packs->sample($key,'en-AT','planner');starterCheck(stripos($planner->content,'Open booking calendar')!==false,'Planner missing booking integration');starterCheck(substr_count($planner->footerHtml,'/assets/starters/certification-')===4,'Certificate slots missing');starterCheck(str_contains($sanitizer->sanitize($planner->footerHtml),'certification-4.svg'),'Footer certificates stripped by editor');}
 $defaultCommand=new App\Command\StarterDefaultCommand($packs);$tester=new Symfony\Component\Console\Tester\CommandTester($defaultCommand);$tester->execute([]);starterCheck($packs->active()['pack_key']==='practice','Practice default not selected');$siteCount=count($packs->all());$tester->execute([]);starterCheck(count($packs->all())===$siteCount,'Default setup duplicated content');
 $switchPage=$packs->sample('linden','en-AT','about');$switchNav=$packs->navigation($switchPage,['pack_key'=>'linden','name'=>'Praxis Linden','prefix'=>'/']);starterCheck(str_contains($switchNav,'lang="de"')&&!str_contains($switchNav,'lang="en"')&&str_contains($switchNav,'href="/about/"'),'Alternate-only language link incorrect');
 
 $postTheme=new App\Entity\Page();$postTheme->kind='Beitrag';$postTheme->locale='en-AT';$postTheme->path='/en/articles/example/';$postTheme->title='Example';$postTheme->content='<h1>Example</h1><p>Article body.</p>';$themed=$packs->contentPresentation($postTheme);starterCheck(str_contains($themed['page']->bodyClass,'pack-practice')&&$themed['page']->content===$postTheme->content&&$themed['page']->path===$postTheme->path,'Article theme changes content or loses active starter');$order=$packs->navigation($packs->sample('practice'),['pack_key'=>'practice','name'=>'Praxis Morgen','prefix'=>'/']);starterCheck(strpos($order,'>Calendar</a>')<strpos($order,'>Appointment</a>')&&strpos($order,'>Appointment</a>')<strpos($order,'class="pack-language"'),'Appointment is not last before language');
 $fonts=new FontLibrary($db,$temporary);starterReject(fn()=>$fonts->import('<?php echo 1;','Bad font'));starterReject(fn()=>$fonts->select('https://evil.example/font.woff2','serif'));
 $bytes=file_get_contents($root.'/public/assets/fonts/geist-sans-variable.woff2');$id=$fonts->import($bytes,'Demo Geist');starterCheck($fonts->file($id)!==null&&count($fonts->all())===1,'Valid font not saved');starterCheck($fonts->import($bytes,'Same font')===$id&&count($fonts->all())===1,'Duplicate font imported');
 $fonts->select($id,'serif');$css=$fonts->css();starterCheck(str_contains($css,'/theme/fonts/'.$id.'.woff2')&&!str_contains($css,'Demo Geist')&&str_contains($css,'Georgia'),'Unsafe/incorrect font CSS');starterCheck($fonts->file('../etc/passwd')===null,'Font path traversal');
 starterReject(fn()=>$fonts->import($bytes,'<script>font</script>'));$bad=$bytes;$bad[11]=chr(ord($bad[11])^1);starterReject(fn()=>$fonts->import($bad,'Broken font'));
 if(in_array('--preview',$argv,true)){
  foreach(array_keys(StarterPacks::CATALOG) as $key){$page=$packs->sample($key);$site=['pack_key'=>$key,'name'=>StarterPacks::CATALOG[$key]['brand'],'prefix'=>'/'];$header=$packs->navigation($page,$site);$html='<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>'.$key.' — original starter preview</title><link rel="stylesheet" href="/assets/demo.css"><link rel="stylesheet" href="/assets/starter-packs.css"><link rel="stylesheet" href="/theme/fonts.css"><link rel="stylesheet" href="/assets/mint-theme.css?v=1"><script src="/assets/demo.js" defer></script></head><body class="pack-'.$key.'">'.$header.$page->content.$page->footerHtml.'</body></html>';file_put_contents($root.'/public/_ui-pack-'.$key.'.html',$html);}
 }
 echo "PASS: five bilingual starter packs; ten drafts each; reversible activation without overwrites; safe editor markup/branding; WOFF2 validation/deduplication/path safety and font selection.\n";
}finally{
 foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($temporary,FilesystemIterator::SKIP_DOTS),RecursiveIteratorIterator::CHILD_FIRST) as $file){if($file->isDir())rmdir($file->getPathname());else unlink($file->getPathname());}rmdir($temporary);
}
