<?php
namespace App\Controller;
use App\Entity\AdminUser;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\{Request,Response};
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
class AdminSetupController extends AbstractController
{
    #[Route(\App\Security\AdminArea::PATH.'/setup', name: 'app_admin_setup', methods: ['GET','POST'])]
    public function setup(Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $hasher, #[Autowire('%kernel.environment%')] string $environment, #[Autowire('%kernel.project_dir%')] string $root): Response
    {
        // Bootstrap is available only on loopback in development, never on a public production host.
        if ($environment!=='dev' || !in_array($request->getClientIp(),['127.0.0.1','::1'],true)) throw $this->createNotFoundException();
        if ($em->getRepository(AdminUser::class)->count([])>0) return $this->redirectToRoute('app_login');
        $errors=[]; $email='';
        if ($request->isMethod('POST')) {
            $email=mb_strtolower(trim($request->request->getString('email')));
            $password=$request->request->getString('password');
            if (!$this->isCsrfTokenValid('setup_admin',$request->request->getString('_token'))) $errors[]='Die Sitzung ist abgelaufen. Bitte versuchen Sie es erneut.';
            if (!filter_var($email,FILTER_VALIDATE_EMAIL) || strlen($email)>180) $errors[]='Bitte geben Sie eine gültige E-Mail-Adresse ein.';
            if (mb_strlen($password)<14 || strlen($password)>72) $errors[]='Bitte verwenden Sie mindestens 14 Zeichen und höchstens 72 Bytes für Ihr Passwort.';
            if ($password!==$request->request->getString('password_confirm')) $errors[]='Die Passwörter stimmen nicht überein.';
            if (!$errors) {
                $lock=fopen($root.'/var/admin-setup.lock','c');
                if (!$lock || !flock($lock,LOCK_EX)) throw new \RuntimeException('Could not lock account setup.');
                try {
                    // Recheck after acquiring the shared bootstrap lock so simultaneous requests cannot register two admins.
                    if ($em->getRepository(AdminUser::class)->count([])>0) return $this->redirectToRoute('app_login');
                    $user=new AdminUser();$user->email=$email;$user->password=$hasher->hashPassword($user,$password);
                    $em->persist($user);$em->flush();
                } finally { flock($lock,LOCK_UN);fclose($lock); }
                $this->addFlash('success','Ihr Administratorkonto wurde erstellt. Melden Sie sich jetzt mit Ihrer E-Mail-Adresse und Ihrem Passwort an.');
                return $this->redirectToRoute('app_login');
            }
        }
        return $this->render('admin/setup.html.twig',['errors'=>$errors,'email'=>$email],new Response(status:$errors?422:200));
    }
}
