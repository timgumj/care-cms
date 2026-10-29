<?php
namespace App\Service;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\TableNotFoundException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
final class FontLibrary
{
 public const BUILTINS=['pack-default'=>'Inter (starter default)','inter'=>'Inter','system'=>'System sans-serif','serif'=>'Georgia / system serif','geist'=>'Geist Sans','inconsolata'=>'Inconsolata'];
 public function __construct(private Connection $db,#[Autowire('%kernel.project_dir%')] private string $root){}
 public function all():array{try{return $this->db->fetchAllAssociative('SELECT id,label,bytes FROM site_font ORDER BY created_at DESC');}catch(TableNotFoundException){return [];}}
 public function selected():array{try{return $this->db->fetchAssociative('SELECT body_font,heading_font FROM site_design WHERE id=1')?:['body_font'=>'pack-default','heading_font'=>'pack-default'];}catch(TableNotFoundException){return ['body_font'=>'pack-default','heading_font'=>'pack-default'];}}
 public function select(string $body,string $heading):void{
  $allowed=array_merge(array_keys(self::BUILTINS),array_column($this->all(),'id'));
  if(!in_array($body,$allowed,true)||!in_array($heading,$allowed,true))throw new \DomainException('Choose a font from the library.');
  $this->db->update('site_design',['body_font'=>$body,'heading_font'=>$heading],['id'=>1]);
 }
 /** Structural checks, not a full OpenType sanitizer. Never executes or fetches uploaded content. */
 public function import(string $bytes,string $label):string{
  $label=trim($label);$length=strlen($bytes);
  if(!preg_match('/^[\pL\pN ._()\-]{2,80}$/uD',$label))throw new \DomainException('Use a plain font name, 2–80 characters.');
  if($length<48||$length>2097152||substr($bytes,0,4)!=='wOF2')throw new \DomainException('Upload a WOFF2 font, maximum 2 MB.');
  $header=unpack('Nlength/ntables/nreserved/Nsfnt/Ncompressed',substr($bytes,8,16));
  if($header['length']!==$length||$header['tables']<1||$header['tables']>128||$header['reserved']!==0||$header['sfnt']>16777216||$header['compressed']<1||$header['compressed']>$length-48)throw new \DomainException('Invalid or oversized WOFF2 font structure.');
  $id=hash('sha256',$bytes);$directory=$this->root.'/var/font-library';if(!is_dir($directory))mkdir($directory,0700,true);
  $lock=fopen($directory.'/library.lock','c');if(!$lock||!flock($lock,LOCK_EX))throw new \RuntimeException('Cannot lock font library.');
  try{
   if($this->db->fetchOne('SELECT id FROM site_font WHERE id=?',[$id]))return $id;
   if((int)$this->db->fetchOne('SELECT COUNT(*) FROM site_font')>=20||(int)$this->db->fetchOne('SELECT COALESCE(SUM(bytes),0) FROM site_font')+$length>20971520)throw new \DomainException('Font library quota reached (20 files / 20 MB).');
   if(file_put_contents($directory.'/'.$id.'.woff2',$bytes,LOCK_EX)!==$length)throw new \RuntimeException('Font upload could not be saved.');
   try{$this->db->insert('site_font',['id'=>$id,'label'=>$label,'bytes'=>$length,'created_at'=>time()]);}catch(\Throwable $e){unlink($directory.'/'.$id.'.woff2');throw $e;}
   return $id;
  }finally{flock($lock,LOCK_UN);fclose($lock);}
 }
 public function file(string $id):?string{
  if(!preg_match('/^[a-f0-9]{64}$/D',$id)||!$this->db->fetchOne('SELECT id FROM site_font WHERE id=?',[$id]))return null;
  $file=$this->root.'/var/font-library/'.$id.'.woff2';return is_file($file)&&!is_link($file)?$file:null;
 }
 public function css():string{
  $selection=$this->selected();$custom=array_column($this->all(),null,'id');$faces=[];$variables=[];
  foreach(['body_font'=>'--site-font-body','heading_font'=>'--site-font-heading'] as $key=>$variable){
   $id=$selection[$key];$family=match($id){'system'=>'"Segoe UI", "Trebuchet MS", sans-serif','inter','pack-default'=>'Inter, sans-serif','serif'=>'Georgia, serif','geist'=>'"Geist Sans", Arial, sans-serif','inconsolata'=>'Inconsolata, monospace',default=>null};
   if(isset($custom[$id])){$family='"CareFont-'.$id.'"';$faces[$id]='@font-face{font-family:'.$family.';src:url("/theme/fonts/'.$id.'.woff2") format("woff2");font-display:swap;font-weight:100 900;}';}
   if($family)$variables[]=$variable.':'.$family.';';
  }
  return '@font-face{font-family:Inter;src:url("/assets/fonts/inter-variable.woff2") format("woff2");font-weight:100 900;font-display:swap;}@font-face{font-family:"Geist Sans";src:url("/assets/fonts/geist-sans-variable.woff2") format("woff2");font-weight:100 900;font-display:swap;}@font-face{font-family:Inconsolata;src:url("/assets/fonts/inconsolata-variable.ttf") format("truetype");font-weight:200 900;font-display:swap;}'.implode('',$faces).':root{'.implode('',$variables).'}';
 }
}
