<?php
namespace App\Controller\Admin;

use App\Security\AdminArea;
use App\Service\CacheMaintenance;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{Request,Response};
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
final class CacheController extends AbstractController
{
    #[Route(AdminArea::PATH.'/cache',name:'admin_cache',methods:['GET','POST'])]
    public function index(Request $request,CacheMaintenance $maintenance): Response
    {
        $error=null;
        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('cache_refresh',$request->request->get('_csrf_token'))) throw $this->createAccessDeniedException();
            try {
                $maintenance->request($this->getUser()->getUserIdentifier());
                return $this->redirectToRoute('admin_cache',['_locale'=>$request->getLocale()],303);
            } catch (\LengthException) { $error='Please wait 30 seconds before clearing the cache again.'; }
            catch (\RuntimeException) { $error='The server cache could not be refreshed. Please check the private directory permissions.'; }
        }
        return $this->render('admin/cache.html.twig',['cache_status'=>$maintenance->status(),'error'=>$error]);
    }
}
