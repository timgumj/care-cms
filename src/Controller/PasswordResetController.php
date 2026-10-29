<?php
namespace App\Controller;
use App\Security\{AdminArea,PasswordResetService};
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{Request,Response};
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
final class PasswordResetController extends AbstractController
{
    #[Route(AdminArea::PATH.'/forgot-password',name:'app_forgot_password',methods:['GET','POST'])]
    public function forgot(Request $request,PasswordResetService $reset): Response {
        $sent=false;$error=null;
        if($request->isMethod('POST')){
            if(!$this->isCsrfTokenValid('password_reset_request',$request->request->get('_csrf_token')))$error='The request expired. Please try again.';
            else {
                $started=microtime(true);$reset->request((string)$request->request->get('email',''),$request->getClientIp()??'unknown',$request->getLocale());
                // Same message and minimum response duration for known, unknown and throttled accounts.
                $remaining=3000000-(int)((microtime(true)-$started)*1000000);if($remaining>0)usleep($remaining);
                return $this->redirectToRoute('app_forgot_password',['sent'=>1],303);
            }
        }
        return $this->render('admin/forgot_password.html.twig',['sent'=>$request->query->get('sent')==='1','error'=>$error]);
    }
    #[Route(AdminArea::PATH.'/reset-password',name:'app_reset_password',methods:['GET','POST'])]
    public function reset(Request $request,PasswordResetService $reset,TokenStorageInterface $tokens): Response {
        $session=$request->getSession();
        if($request->isMethod('GET')&&$request->query->has('token')){
            $hash=$reset->hashToken((string)$request->query->get('token',''));$session->remove('_password_reset_hash');
            if($hash&&$reset->valid($hash))$session->set('_password_reset_hash',$hash);
            // Strip the sensitive token before rendering or loading other resources. GET never consumes it.
            return $this->redirectToRoute('app_reset_password',[],303);
        }
        $hash=$session->get('_password_reset_hash','');$valid=is_string($hash)&&$reset->valid($hash);$error=null;
        if($request->isMethod('POST')&&$valid){
            $password=(string)$request->request->get('password','');$confirm=(string)$request->request->get('confirm_password','');
            if(!$this->isCsrfTokenValid('password_reset_submit',$request->request->get('_csrf_token')))$error='The request expired. Please try again.';
            elseif(mb_strlen($password)<14||strlen($password)>72)$error='Use at least 14 characters and at most 72 bytes.';
            elseif($password!==$confirm)$error='The passwords do not match.';
            elseif($reset->reset($hash,$password)){
                $tokens->setToken(null);$session->invalidate();$this->addFlash('success','Your password has been updated. Sign in with your new password.');return $this->redirectToRoute('app_login',[],303);
            }else $valid=false;
        }
        return $this->render('admin/reset_password.html.twig',['valid'=>$valid,'error'=>$error]);
    }
}
