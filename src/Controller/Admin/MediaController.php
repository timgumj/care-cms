<?php
namespace App\Controller\Admin;

use App\Security\AdminArea;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_EDITOR')]
final class MediaController extends AbstractController
{
    #[Route(AdminArea::PATH.'/media',name:'admin_media',methods:['GET'])]
    public function index(): Response
    {
        return $this->render('admin/media.html.twig');
    }
}
