<?php
require __DIR__.'/../vendor/autoload.php';
use Symfony\Component\HttpFoundation\Request;
function uiCheck(bool $value,string $message):void{if(!$value)throw new RuntimeException($message);}
$root=dirname(__DIR__);$templates=[];
foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root.'/templates')) as $file)if($file->isFile()&&str_ends_with($file->getFilename(),'.twig')){$key=str_replace('\\','/',substr($file->getPathname(),strlen($root.'/templates/')));$templates[$key]=file_get_contents($file->getPathname());}
$templates['@EasyAdmin/page/content.html.twig']='{% block main %}{% endblock %}';
$role='ROLE_ADMIN';
$twig=new Twig\Environment(new Twig\Loader\ArrayLoader($templates),['strict_variables'=>true]);
// Shared mutable role for role-boundary render checks.
$grants=new class{public bool $admin=true;};
$twig->addFunction(new Twig\TwigFunction('is_granted',fn($permission)=>$grants->admin||$permission==='ROLE_EDITOR'));
$twig->addFunction(new Twig\TwigFunction('path',function($name,$args=[]){
 $routes=['admin'=>'/manage','admin_media'=>'/manage/media','admin_calendar'=>'/manage/calendar','admin_cache'=>'/manage/cache','admin_menus'=>'/manage/menus','admin_announcements'=>'/manage/announcements','admin_users'=>'/manage/users','admin_starters'=>'/manage/starters','admin_fonts'=>'/manage/fonts','admin_session_settings'=>'/manage/session-settings','admin_trash'=>'/manage/trash','app_logout_confirm'=>'/manage/sign-out'];
 if($name==='admin_starter_preview')return '/_ui-pack-'.$args['key'].'.html';
 if($name==='admin_starter_site')return '/manage/starters/site/'.$args['id'];
 if($name==='admin_content_link')return '/manage/content/'.$args['kind'].'/'.$args['action'];
 if($name==='admin_edit_page')return '/manage/edit-page/'.$args['id'];
 return $routes[$name]??'/manage/'.$name;
}));
$twig->addFunction(new Twig\TwigFunction('csrf_token',fn()=>''));$twig->addFilter(new Twig\TwigFilter('trans',fn($s)=>$s));
$request=Request::create('/en/');$request->setLocale('en');$request->attributes->set('_route','site_page');
$user=new App\Entity\AdminUser();$user->id=1;$user->email='reviewer@example.test';$user->displayName='Alex Reviewer';
$app=new class($request,$user){public function __construct(public $request,public $user){}public function flashes($type){return [];}};
$page=new App\Entity\Page();$page->id=7;$page->title='Demo page';$page->locale='en-AT';$page->canonical='http://127.0.0.1:8020/en/';
$context=['app'=>$app,'page'=>$page,'content'=>'<main><h1>Example</h1></main>','header'=>'<header>Demo</header>','alternates'=>[]];
$anonymous=$twig->render('site/page.html.twig',$context);uiCheck(!str_contains($anonymous,'class="cms-toolbar"'),'Toolbar exposed to anonymous visitors');
$request->attributes->set('_admin_session_active',true);
$logged=$twig->render('site/page.html.twig',$context);uiCheck(substr_count($logged,'class="cms-toolbar"')===1,'Toolbar missing or duplicated');uiCheck(str_contains($logged,'/manage/edit-page/7'),'Contextual page editor link incorrect');uiCheck(str_contains($logged,'Calendar')&&str_contains($logged,'Manage users'),'Admin toolbar actions missing');
$user->displayName='<script>alert(1)</script>';$escaped=$twig->render('admin/_toolbar.html.twig',$context);uiCheck(!str_contains($escaped,'<script>alert(1)</script>'),'User label not escaped');$user->displayName='Alex Reviewer';
$grants->admin=false;$editor=$twig->render('admin/_toolbar.html.twig',$context);uiCheck(!str_contains($editor,'/manage/calendar')&&!str_contains($editor,'/manage/cache')&&!str_contains($editor,'Manage users'),'Privileged toolbar links exposed to editors');
$sidebar=$twig->render('admin/_sidebar_links.html.twig',$context);uiCheck(!str_contains($sidebar,'/manage/users')&&!str_contains($sidebar,'/manage/calendar'),'Privileged sidebar links exposed');
$grants->admin=true;$request->attributes->set('_route','admin');
foreach(['admin/starters.html.twig','admin/starter_site.html.twig','admin/fonts.html.twig','admin/_toolbar.html.twig','admin/_sidebar_links.html.twig','admin/_sidebar.html.twig','admin/auth_base.html.twig','admin/dashboard.html.twig'] as $name)$twig->parse($twig->tokenize(new Twig\Source($templates[$name],$name)));
$now=new DateTimeImmutable();$page->updatedAt=$now;$page->path='/en/';
$dashboardContext=$context+['active_starter'=>null,'font_count'=>0,'page_count'=>6,'post_count'=>2,'draft_count'=>0,'trash_count'=>0,'german_pages'=>4,'english_pages'=>4,'total_pages'=>8,'published_pages'=>8,'recent_pages'=>[$page],'next_bookings'=>[],'booking_count'=>0];
$dashboard=$twig->render('admin/dashboard.html.twig',$dashboardContext);uiCheck(str_contains($dashboard,'Recent content')&&str_contains($dashboard,'Upcoming appointments'),'Dashboard panels missing');
$request->setLocale('de');$german=$twig->render('admin/dashboard.html.twig',$dashboardContext);uiCheck(str_contains($german,'Zuletzt bearbeitet'),'German dashboard labels missing');
// Reproduce EasyAdmin child templates replacing body_class (the real dashboard behaviour).
$shellTwig=new Twig\Environment(new Twig\Loader\ArrayLoader([
 '@!EasyAdmin/layout.html.twig'=>'<body {% block body_attr %}{% endblock %} class="ea {% block body_class %}{% endblock %}">{% block header_custom_menu_wrapper %}<div>DUPLICATE ACCOUNT</div>{% endblock %}</body>',
 'shell'=>file_get_contents($root.'/templates/bundles/EasyAdminBundle/layout.html.twig'),
 'child'=>'{% extends "shell" %}{% block body_class %}page-content{% endblock %}'
]));
$shellTwig->addFunction(new Twig\TwigFunction('path',fn()=>'/manage'));$shellTwig->addFunction(new Twig\TwigFunction('csrf_token',fn()=>''));$shell=$shellTwig->render('child');uiCheck(str_contains($shell,'data-cms-backend="true"')&&str_contains($shell,'page-content')&&!str_contains($shell,'DUPLICATE ACCOUNT'),'EasyAdmin child override breaks shell scope or duplicates account row');
// Every new icon must resolve to a local SVG symbol.
$sprite=new DOMDocument();$sprite->loadXML(file_get_contents($root.'/public/assets/admin-icons.svg'));$ids=[];foreach($sprite->getElementsByTagName('symbol') as $symbol)$ids[]=$symbol->getAttribute('id');foreach(['workspace','edit','plus','calendar','refresh','menu','users','notice'] as $icon)uiCheck(in_array($icon,$ids,true),'Missing icon '.$icon);
if(in_array('--preview',$argv,true)){
 $request->setLocale('en');$request->attributes->set('_route','admin');
 $toolbar=$twig->render('admin/_toolbar.html.twig',$context+['backend'=>true]);$sidebar=$twig->render('admin/_sidebar.html.twig',$context);
 $html='<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Care CMS design preview (fictional fixtures)</title><link rel="stylesheet" href="/assets/admin-theme.css?v=5"><link rel="stylesheet" href="/assets/cms-toolbar.css?v=1"><link rel="stylesheet" href="/assets/cms-workspace.css?v=1"><script src="/assets/cms-workspace.js?v=1" defer></script></head><body class="studio-auth cms-workspace cms-has-toolbar">'.$toolbar.$sidebar.'<div class="auth-main"><main class="auth-card"><h1>Dashboard</h1>'.$twig->render('admin/dashboard.html.twig',$dashboardContext).'</main></div></body></html>';
 file_put_contents($root.'/public/_ui-preview.html',$html);
 $request->attributes->set('_route','site_page');
 $front=$context;$front['header']=$twig->render('site/_navigation.html.twig',$context+['english'=>true,'items'=>[['url'=>'/en/','title'=>'Home','button'=>false,'current'=>true,'new_tab'=>false],['url'=>'/en/services/','title'=>'Services','button'=>false,'current'=>false,'new_tab'=>false],['url'=>'/booking/','title'=>'Book an appointment','button'=>true,'current'=>false,'new_tab'=>false]],'language_links'=>['de'=>'/','en'=>'/en/'],'current_locale'=>'en']);
 $front['content']='<main><section class="hero"><p class="eyebrow">CARE STUDIO · DEMO</p><h1>Space for your wellbeing.</h1><p>A calm, bilingual website for a fictional health practice.</p><a class="button" href="/booking/">Book a demo appointment</a></section></main>';
 file_put_contents($root.'/public/_ui-front-preview.html',$twig->render('site/page.html.twig',$front));
 $request->attributes->set('_route','admin_starters');$request->setLocale('en');
 file_put_contents($root.'/public/_ui-starters-gallery.html',$twig->render('admin/starters.html.twig',['app'=>$app,'packs'=>App\Service\StarterPacks::CATALOG,'sites'=>[],'error'=>null]));
 $request->attributes->set('_route','admin_fonts');
 file_put_contents($root.'/public/_ui-fonts.html',$twig->render('admin/fonts.html.twig',['app'=>$app,'fonts'=>[],'builtins'=>App\Service\FontLibrary::BUILTINS,'selected'=>['body_font'=>'pack-default','heading_font'=>'pack-default'],'error'=>null]));
 
}
echo "PASS: toolbar visibility, contextual edit link, editor/admin link boundaries, escaped account names, bilingual dashboard rendering and local SVG icons.\n";
