<?php
namespace App\Controller;
use App\Entity\AdminUser;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\{Request,Response};
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
class LoginController extends AbstractController
{
    #[Route(\App\Security\AdminArea::PATH.'/login', name: 'app_login', methods: ['GET','POST'])]
    public function login(Request $request, AuthenticationUtils $auth, EntityManagerInterface $em, #[Autowire('%kernel.environment%')] string $environment, #[Autowire('%env(bool:EMAIL_2FA_ENABLED)%')] bool $email2fa): Response
    {
        return $this->render('admin/login.html.twig',[
            'email_2fa_enabled'=>$environment==='prod'||$email2fa,
            'error'=>$auth->getLastAuthenticationError(),
            'last_username'=>$auth->getLastUsername(),
            'needs_setup'=>$environment==='dev' && in_array($request->getClientIp(),['127.0.0.1','::1'],true) && $em->getRepository(AdminUser::class)->count([])===0,
        ]);
    }
    #[Route(\App\Security\AdminArea::PATH.'/sign-out', name: 'app_logout_confirm', methods: ['GET'])]
    public function confirmLogout(): Response { return $this->render('admin/logout.html.twig'); }
    #[Route(\App\Security\AdminArea::PATH.'/logout', name: 'app_logout', methods: ['POST'])]
    public function logout(): never { throw new \LogicException('Handled by the security firewall.'); }
}
