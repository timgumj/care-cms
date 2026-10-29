<?php
namespace App\Controller;
use App\Entity\Page;
use App\Security\EditorContentSanitizer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\{Request,JsonResponse};
use Symfony\Component\Routing\Attribute\Route;

/** Published content only. Deliberately no write, user, or appointment API. */
final class ContentApiController extends AbstractController
{
 #[Route('/api/v1/pages',name:'content_api',methods:['GET','OPTIONS'])]
 public function pages(Request $r,EntityManagerInterface $em,EditorContentSanitizer $sanitizer,#[Autowire('%env(API_ALLOWED_ORIGIN)%')] string $allowedOrigin,?\App\Service\StarterPacks $packs=null): JsonResponse {
  $headers=['X-Content-Type-Options'=>'nosniff','Cache-Control'=>'public, max-age=60','Vary'=>'Origin'];
  if($r->headers->get('Origin')===$allowedOrigin&&$allowedOrigin!=='') {
   $headers['Access-Control-Allow-Origin']=$allowedOrigin;$headers['Access-Control-Allow-Methods']='GET, OPTIONS';
  }
  if($r->isMethod('OPTIONS'))return new JsonResponse(null,204,$headers);
  $locale=$r->query->getString('locale','en-AT');
  if(!in_array($locale,['de-AT','en-AT'],true))return new JsonResponse(['error'=>'Use locale=de-AT or en-AT'],400,$headers);
  $active=$packs?->active();
  $criteria=['locale'=>$locale,'published'=>true,'deletedAt'=>null];
  if($r->query->has('path'))$criteria['path']=($active?$active['prefix']:'/').ltrim($r->query->getString('path'),'/');
  $items=[];
  $query=$em->getRepository(Page::class)->createQueryBuilder('p')->where('p.locale=:locale AND p.published=true AND p.deletedAt IS NULL')->setParameter('locale',$locale)->orderBy('p.path','ASC')->setMaxResults(100);
  if(isset($criteria['path']))$query->andWhere('(p.path=:path OR p.path=:alias)')->setParameter('path',$criteria['path'])->setParameter('alias',$r->query->getString('path'));
  if($active)$query->andWhere('(p.translationKey LIKE :key OR p.bodyClass LIKE :site)')->setParameter('key','starter-'.$active['id'].'-%')->setParameter('site','%starter-site-'.$active['id'].'%');
  foreach($query->getQuery()->getResult() as $page) {
   if($active&&($packs->siteForPage($page)['id']??null)!==$active['id'])continue;
   if($active&&str_starts_with($page->path,$active['prefix']))$page=$packs->present($page,$active);
   $items[]=['path'=>$page->path,'type'=>$page->kind==='Beitrag'?'article':'page','locale'=>$page->locale,'translationKey'=>$page->translationKey,'title'=>$page->title,'description'=>$page->description,'html'=>$sanitizer->sanitize($page->content),'updatedAt'=>$page->updatedAt->format(DATE_ATOM)];
  }
  return new JsonResponse(['version'=>'1','items'=>$items],200,$headers);
 }
}
