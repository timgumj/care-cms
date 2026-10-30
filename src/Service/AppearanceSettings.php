<?php
namespace App\Service;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
final class AppearanceSettings {
 public function __construct(#[Autowire('%kernel.project_dir%')] private string $root){}
 public function mode():string { $file=$this->root.'/var/security/appearance.json';$data=is_file($file)?json_decode((string)file_get_contents($file),true):[];return ($data['desktop_menu']??'inline')==='hamburger'?'hamburger':'inline'; }
 public function save(string $mode):void {if(!in_array($mode,['inline','hamburger'],true))throw new \DomainException('Choose a valid desktop menu style.');$dir=$this->root.'/var/security';if(!is_dir($dir))mkdir($dir,0700,true);$temp=tempnam($dir,'appearance-');try{if(file_put_contents($temp,json_encode(['desktop_menu'=>$mode]),LOCK_EX)===false)throw new \RuntimeException('Could not save appearance.');chmod($temp,0600);if(!rename($temp,$dir.'/appearance.json'))throw new \RuntimeException('Could not save appearance.');}finally{if(is_file($temp))unlink($temp);}}
}
