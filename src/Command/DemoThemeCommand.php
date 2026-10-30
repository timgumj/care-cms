<?php
namespace App\Command;
use App\Service\StarterPacks;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
#[AsCommand(name:'app:demo-theme',description:'Install the mint Practice theme as a new site, keeping existing starter pages available.')]
final class DemoThemeCommand extends Command {
 public function __construct(private StarterPacks $packs){parent::__construct();}
 protected function execute(InputInterface $input,OutputInterface $output):int {
  foreach($this->packs->all() as $site)if($site['name']==='Practice mint default'){$output->writeln('Mint theme already installed. Existing selection preserved.');return Command::SUCCESS;}
  $active=$this->packs->active();$site=$this->packs->create('practice','Practice mint default');
  if(!$active||$active['pack_key']==='practice')$this->packs->activate($site['id']);
  $output->writeln('Mint theme installed. Previous starter pages retained; other selected designs unchanged.');return Command::SUCCESS;
 }
}
