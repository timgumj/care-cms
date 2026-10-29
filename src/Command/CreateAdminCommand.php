<?php
namespace App\Command;
use App\Entity\AdminUser;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
#[AsCommand(name: 'app:create-admin', description: 'Create an administrator with a hidden password prompt.')]
class CreateAdminCommand extends Command
{
    public function __construct(private EntityManagerInterface $em, private UserPasswordHasherInterface $hasher) { parent::__construct(); }
    protected function configure(): void { $this->addArgument('email', InputArgument::REQUIRED); }
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output); $email = strtolower(trim($input->getArgument('email')));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { $io->error('Enter a valid email.'); return Command::INVALID; }
        if ($this->em->getRepository(AdminUser::class)->findOneBy(['email'=>$email])) { $io->error('Account already exists.'); return Command::INVALID; }
        $password = $io->askHidden('Choose a password (at least 14 characters)', function ($value) { if (!is_string($value) || mb_strlen($value)<14 || strlen($value)>72) throw new \RuntimeException('Use at least 14 characters and at most 72 bytes.'); return $value; });
        $user = new AdminUser(); $user->email=$email; $user->password=$this->hasher->hashPassword($user,$password);
        $this->em->persist($user); $this->em->flush(); $io->success('Administrator created.'); return Command::SUCCESS;
    }
}
