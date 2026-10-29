<?php
namespace App\Security;
use App\Entity\AdminUser;
use Symfony\Component\Security\Core\User\{UserCheckerInterface,UserInterface};
use Symfony\Component\Security\Core\Exception\DisabledException;
final class AccountChecker implements UserCheckerInterface
{
    public function checkPreAuth(UserInterface $user):void { if(!$user instanceof AdminUser || !$user->enabled || !$user->passwordSet || !$user->getRoles()) throw new DisabledException('This account is not available.'); }
    public function checkPostAuth(UserInterface $user):void { $this->checkPreAuth($user); }
}
