<?php
namespace App\Entity;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
#[ORM\Entity]
class AdminUser implements UserInterface, PasswordAuthenticatedUserInterface, \Symfony\Component\Security\Core\User\EquatableInterface
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column] public ?int $id = null;
    #[ORM\Column(length: 180, unique: true)] public string $email = '';
    #[ORM\Column] public string $password = '';
    #[ORM\Column(length:120, options:['default'=>''])] public string $displayName = '';
    #[ORM\Column(length:32, options:['default'=>'ROLE_ADMIN'])] public string $accessRole = 'ROLE_ADMIN';
    #[ORM\Column(options:['default'=>true])] public bool $enabled = true;
    #[ORM\Column(options:['default'=>true])] public bool $passwordSet = true;
    #[ORM\Column(options:['default'=>1])] public int $securityVersion = 1;
    public function getUserIdentifier(): string { return $this->email; }
    public function getRoles(): array { return $this->enabled&&$this->passwordSet&&in_array($this->accessRole,['ROLE_ADMIN','ROLE_EDITOR'],true)?[$this->accessRole]:[]; }
    public function getPassword(): string { return $this->password; }
    // Symfony 7.4 compares this fingerprint to a freshly loaded password on every request.
    // Keep the real password hash out of serialized session files.
    public function __serialize(): array {return ['id'=>$this->id,'email'=>$this->email,'password'=>hash('crc32c',$this->password),'displayName'=>$this->displayName,'accessRole'=>$this->accessRole,'enabled'=>$this->enabled,'passwordSet'=>$this->passwordSet,'securityVersion'=>$this->securityVersion];}
    public function isEqualTo(UserInterface $user): bool {
        return $user instanceof self && $this->id===$user->id && $this->email===$user->email && $this->accessRole===$user->accessRole && $this->enabled===$user->enabled && $this->passwordSet===$user->passwordSet && $this->securityVersion===$user->securityVersion
            && (hash_equals($this->password,$user->password)||(strlen($this->password)===8&&hash_equals($this->password,hash('crc32c',$user->password))));
    }
    public function eraseCredentials(): void {}
}
