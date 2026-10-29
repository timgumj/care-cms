<?php
namespace App\Service;
use App\Entity\AdminUser;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class UserManagement
{
    public function __construct(private EntityManagerInterface $em, private UserPasswordHasherInterface $hasher, #[Autowire(service:'app.security_lock_factory')] private LockFactory $locks) {}
    private function values(string $name,string $role):void {
        if(mb_strlen($name)>120||!in_array($role,['ROLE_ADMIN','ROLE_EDITOR'],true))throw new \DomainException('Please check the name and account role.');
    }
    private function change(int $actor,callable $callback):mixed {
        $lock=$this->locks->createLock('user-management');if(!$lock->acquire(true))throw new \RuntimeException('User management is busy.');
        try{return $this->em->getConnection()->transactional(function()use($actor,$callback){
            $record=$this->em->getConnection()->fetchAssociative('SELECT accessRole, enabled, passwordSet FROM AdminUser WHERE id = ?',[$actor]);
            if(!$record||$record['accessRole']!=='ROLE_ADMIN'||!(bool)$record['enabled']||!(bool)$record['passwordSet'])throw new \DomainException('Administrator access is required.');
            return $callback();
        });}finally{$lock->release();}
    }
    public function create(int $actor,string $email,string $name,string $role):AdminUser {
        $email=mb_strtolower(trim($email));$name=trim($name);$this->values($name,$role);
        if(strlen($email)>180||!filter_var($email,FILTER_VALIDATE_EMAIL))throw new \DomainException('Enter a valid email address.');
        return $this->change($actor,function()use($email,$name,$role){
            if($this->em->getRepository(AdminUser::class)->count([])>=100)throw new \DomainException('The site supports up to 100 accounts.');
            if($this->em->getRepository(AdminUser::class)->findOneBy(['email'=>$email]))throw new \DomainException('An account already exists with that email address.');
            $user=new AdminUser();$user->email=$email;$user->displayName=$name;$user->accessRole=$role;$user->passwordSet=false;
            $user->password=$this->hasher->hashPassword($user,bin2hex(random_bytes(32)));
            $this->em->persist($user);$this->em->flush();return $user;
        });
    }
    public function update(int $actor,int $id,string $name,string $role,bool $enabled):void {
        $name=trim($name);$this->values($name,$role);
        $this->change($actor,function()use($actor,$id,$name,$role,$enabled){
            $db=$this->em->getConnection();$row=$db->fetchAssociative('SELECT * FROM AdminUser WHERE id = ?',[$id]);if(!$row)throw new \DomainException('Account not found.');
            if($actor===$id&&($role!=='ROLE_ADMIN'||!$enabled))throw new \DomainException('You cannot remove your own administrator access.');
            if($row['accessRole']==='ROLE_ADMIN'&&(bool)$row['enabled']&&(bool)$row['passwordSet']&&($role!=='ROLE_ADMIN'||!$enabled)){
                $count=(int)$db->fetchOne("SELECT COUNT(*) FROM AdminUser WHERE accessRole = 'ROLE_ADMIN' AND enabled = 1 AND passwordSet = 1");
                if($count<=1)throw new \DomainException('Keep at least one active administrator with a password.');
            }
            $changed=$row['accessRole']!==$role||(bool)$row['enabled']!==$enabled;
            $db->update('AdminUser',['displayName'=>$name,'accessRole'=>$role,'enabled'=>(int)$enabled,'securityVersion'=>(int)$row['securityVersion']+($changed?1:0)],['id'=>$id]);
            if($changed){$db->delete('admin_email_challenge',['user_id'=>$id]);$db->delete('admin_password_reset',['user_id'=>$id]);}
            $this->em->clear();
        });
    }
}
