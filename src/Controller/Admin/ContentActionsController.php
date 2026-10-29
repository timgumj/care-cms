<?php
namespace App\Controller\Admin;
use App\Entity\Page;
use App\Security\AdminArea;
use App\Service\ContentLifecycle;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGeneratorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{Request, Response};
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_EDITOR')]
final class ContentActionsController extends AbstractController
{
    #[Route(AdminArea::PATH.'/trash', name:'admin_trash', methods:['GET'])]
    public function trash(EntityManagerInterface $em): Response {
        $pages=$em->getRepository(Page::class)->createQueryBuilder('page')->where('page.deletedAt IS NOT NULL')->orderBy('page.deletedAt','DESC')->getQuery()->getResult();
        return $this->render('admin/trash.html.twig',['pages'=>$pages]);
    }
    #[Route(AdminArea::PATH.'/content/{id}/{operation}', name:'admin_content_action', requirements:['id'=>'\d+','operation'=>'trash|restore|purge|english'], methods:['GET','POST'])]
    public function action(int $id, string $operation, Request $request, EntityManagerInterface $em, ContentLifecycle $lifecycle, AdminUrlGeneratorInterface $urls): Response {
        if($operation==='purge')$this->denyAccessUnlessGranted('ROLE_ADMIN');
        $page=$em->find(Page::class,$id);if(!$page)throw $this->createNotFoundException();
        $trashed=$page->deletedAt!==null;
        if(in_array($operation,['restore','purge'],true)!==$trashed)throw $this->createNotFoundException();
        if($operation==='english' && ($page->kind!=='Beitrag'||$page->locale!=='de-AT'))throw $this->createNotFoundException();
        $error=null;
        if($request->isMethod('POST')) {
            if(!$this->isCsrfTokenValid('content_'.$operation.'_'.$id,$request->request->get('_csrf_token')))throw $this->createAccessDeniedException();
            try {
                if($operation==='english') {
                    $draft=$lifecycle->englishDraft($page);
                    return $this->redirect($urls->unsetAll()->setDashboard(DashboardController::class)->setController(PostCrudController::class)->setAction('edit')->setEntityId($draft->id)->generateUrl());
                }
                match($operation) {'trash'=>$lifecycle->trash($page),'restore'=>$lifecycle->restore($page),'purge'=>$lifecycle->permanentlyDelete($page)};
                return $this->redirectToRoute('admin_trash');
            } catch(\DomainException $exception) {$error=$exception->getMessage();}
        }
        $response=$this->render('admin/content_confirm.html.twig',['page'=>$page,'operation'=>$operation,'error'=>$error]);
        if($error)$response->setStatusCode(409);
        return $response;
    }
}
