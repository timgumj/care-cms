<?php
namespace App\Command;
use App\Service\StarterPacks;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
#[AsCommand(name:'app:starter-default',description:'Install the Practice default once, preserving all existing content.')]
final class StarterDefaultCommand extends Command {
 public function __construct(private StarterPacks $packs){parent::__construct();}
 protected function execute(InputInterface $input,OutputInterface $output):int {
  foreach($this->packs->all() as $site)if($site['name']==='Practice default'){ $output->writeln('Practice default already installed; current selection preserved.');return Command::SUCCESS; }
  $site=$this->packs->create('practice','Practice default');$this->packs->activate($site['id']);
  $output->writeln('Practice is now the default. Existing sites, pages and bookings retained.');return Command::SUCCESS;
 }
}
