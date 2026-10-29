<?php
namespace App\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
final class UploadBudget
{
 public function __construct(#[Autowire('%kernel.project_dir%')] private string $root){}
 public function run(string $directory,int $incoming,callable $save):mixed {
  $private=$this->root.'/var/security';if(!is_dir($private))mkdir($private,0700,true);
  $lock=fopen($private.'/uploads.lock','c');if(!$lock||!flock($lock,LOCK_EX))throw new \RuntimeException('Could not lock upload budget.');
  try{
   $size=0;$count=0;
   foreach(new \DirectoryIterator($directory) as $file){if($file->isDot()||$file->isLink()||!$file->isFile()||!in_array(strtolower($file->getExtension()),['jpg','jpeg','png','webp','gif'],true))continue;$size+=$file->getSize();$count++;}
   // Reserve the maximum possible size, then enforce 5 MB on each encoded file too.
   if($count+$incoming>1000||$size+$incoming*5*1024*1024>100*1024*1024)throw new \LengthException('Image storage limit reached.');
   return $save();
  }finally{flock($lock,LOCK_UN);fclose($lock);}
 }
}
