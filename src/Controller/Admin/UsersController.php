<?php
namespace App\Controller\Admin;
use App\Entity\AdminUser;
use App\Security\{AdminArea,PasswordResetService};
use App\Service\UserManagement;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{Request,Response};
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
final class UsersController extends AbstractController
{
    #[Route(AdminArea::PATH.'/users',name:'admin_users',methods:['GET','POST'])]
    public function index(Request $request,EntityManagerInterface $em,UserManagement $users,PasswordResetService $reset):Response {
        $error=null;$values=['email'=>'','name'=>'','role'=>'ROLE_EDITOR'];
        if($request->isMethod('POST')){
            if(!$this->isCsrfTokenValid('user_create',$request->request->get('_csrf_token')))throw $this->createAccessDeniedException();
            $values=['email'=>$request->request->getString('email'),'name'=>$request->request->getString('displayName'),'role'=>$request->request->getString('accessRole')];
            try {
                $user=$users->create($this->getUser()->id,$values['email'],$values['name'],$values['role']);
                $sent=$reset->invitation($user,$request->getClientIp()??'unknown',$request->getLocale());
                $this->addFlash('users_notice',$sent?'Account created. The invitation email was sent.':'Account created, but the invitation could not be sent. Try Send invitation again later.');
                return $this->redirectToRoute('admin_users',['_locale'=>$request->getLocale()],303);
            }catch(\DomainException $exception){$error=$exception->getMessage();}
            catch(\Doctrine\DBAL\Exception\UniqueConstraintViolationException){$error='An account already exists with that email address.';}
        }
        return $this->render('admin/users.html.twig',['users'=>$em->getRepository(AdminUser::class)->findBy([],['email'=>'ASC']),'values'=>$values,'error'=>$error]);
    }
    #[Route(AdminArea::PATH.'/users/{id}',name:'admin_user_edit',requirements:['id'=>'\d+'],methods:['GET','POST'])]
    public function edit(int $id,Request $request,EntityManagerInterface $em,UserManagement $users):Response {
        $user=$em->find(AdminUser::class,$id);if(!$user)throw $this->createNotFoundException();$error=null;
        if($request->isMethod('POST')){
            if(!$this->isCsrfTokenValid('user_edit_'.$id,$request->request->get('_csrf_token')))throw $this->createAccessDeniedException();
            try {
                $users->update($this->getUser()->id,$id,$request->request->getString('displayName'),$request->request->getString('accessRole'),$request->request->getBoolean('enabled'));
                $this->addFlash('users_notice','Account updated. Permission and status changes revoke existing sessions.');
                return $this->redirectToRoute('admin_users',['_locale'=>$request->getLocale()],303);
            }catch(\DomainException $exception){$error=$exception->getMessage();}
        }
        return $this->render('admin/user_edit.html.twig',['account'=>$user,'own_account'=>$user->id===$this->getUser()->id,'error'=>$error]);
    }
    #[Route(AdminArea::PATH.'/users/{id}/invitation',name:'admin_user_invitation',requirements:['id'=>'\d+'],methods:['POST'])]
    public function invitation(int $id,Request $request,EntityManagerInterface $em,PasswordResetService $reset):Response {
        if(!$this->isCsrfTokenValid('user_invite_'.$id,$request->request->get('_csrf_token')))throw $this->createAccessDeniedException();
        $user=$em->find(AdminUser::class,$id);if(!$user)throw $this->createNotFoundException();
        if(!$user->enabled||$user->passwordSet)throw $this->createAccessDeniedException();
        $sent=$reset->invitation($user,$request->getClientIp()??'unknown',$request->getLocale());
        $this->addFlash('users_notice',$sent?'Invitation sent. The link expires in 30 minutes.':'The invitation could not be sent. Check email delivery or try again later.');
        return $this->redirectToRoute('admin_users',['_locale'=>$request->getLocale()],303);
    }
}
