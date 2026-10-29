<?php
namespace App\Controller\Admin;
use App\Entity\Page;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
#[IsGranted('ROLE_EDITOR')]
final class ContentLinksController extends AbstractController
{
 #[Route('/manage/content/{kind}/{action}',name:'admin_content_link',requirements:['kind'=>'pages|articles','action'=>'index|new'],methods:['GET'])]
 public function index(string $kind,string $action,AdminUrlGenerator $urls): Response {
  return $this->redirect($urls->unsetAll()->setDashboard(DashboardController::class)->setController($kind==='articles'?PostCrudController::class:PageCrudController::class)->setAction($action)->generateUrl());
 }
 #[Route('/manage/edit-page/{id}',name:'admin_edit_page',requirements:['id'=>'[1-9][0-9]*'],methods:['GET'])]
 public function edit(int $id,EntityManagerInterface $em,AdminUrlGenerator $urls): Response {
  $page=$em->find(Page::class,$id);if(!$page||$page->deletedAt)throw $this->createNotFoundException();
  return $this->redirect($urls->unsetAll()->setDashboard(DashboardController::class)->setController($page->kind==='Beitrag'?PostCrudController::class:PageCrudController::class)->setAction('edit')->setEntityId($id)->generateUrl());
 }
}
