<?php
namespace App\Service;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Filesystem;

final class MenuSettings
{
    public function __construct(#[Autowire('%kernel.project_dir%')] private string $root) {}
    public function defaults(): array {
        $menus=[];
        foreach(['de','en'] as $locale){
            $labels=$locale==='de'?['Home','Über uns','Meine Leistungen','Kontakt','Termin']:['Home','About','Services','Articles','Appointment'];
            $paths=$locale==='de'?['/','/about/','/services/','/artikel/']:['/en/','/en/about/','/en/services/','/en/articles/'];
            foreach($paths as $index=>$path)$menus[$locale][]=['title'=>$labels[$index],'type'=>str_contains($path,'artikel')||str_contains($path,'articles')?'custom':'page','url'=>$path,'new_tab'=>false,'button'=>false];
            $menus[$locale][]=['title'=>$labels[4],'type'=>'custom','url'=>'/booking/','new_tab'=>true,'button'=>true];
        }
        return $menus;
    }
    public function read(): array {
        $path=$this->root.'/var/security/menus.json';if(!is_file($path))return $this->defaults();
        $values=json_decode((string)file_get_contents($path),true);
        return is_array($values)&&isset($values['de'],$values['en'])&&$this->valid($values['de'])&&$this->valid($values['en'])?$values:$this->defaults();
    }
    public function valid(mixed $items): bool {
        if(!is_array($items)||!array_is_list($items)||count($items)>30)return false;
        foreach($items as $item){
            if(!is_array($item)||!is_string($item['title']??null)||trim($item['title'])===''||mb_strlen($item['title'])>100)return false;
            if(!in_array($item['type']??null,['page','custom'],true)||!is_string($item['url']??null)||mb_strlen($item['url'])>2048||!$this->safeUrl($item['url']))return false;
            if($item['type']==='page'&&!preg_match('~^/(?:[a-z0-9-]+/)*$~D',$item['url']))return false;
            if(!is_bool($item['new_tab']??null)||!is_bool($item['button']??null))return false;
        }
        return true;
    }
    public function safeUrl(string $url): bool {
        if($url===''||preg_match('/[\x00-\x20\\\\]/',$url))return false;
        if(str_starts_with($url,'/')&&!str_starts_with($url,'//'))return true;
        if(preg_match('/^#[A-Za-z][A-Za-z0-9_-]*$/D',$url))return true;
        if(str_starts_with($url,'mailto:'))return filter_var(substr($url,7),FILTER_VALIDATE_EMAIL)!==false;
        if(str_starts_with($url,'tel:'))return (bool)preg_match('/^tel:\+?[0-9()\-]{3,30}$/D',$url);
        return filter_var($url,FILTER_VALIDATE_URL)!==false&&strtolower((string)parse_url($url,PHP_URL_SCHEME))==='https'&&!parse_url($url,PHP_URL_USER)&&!parse_url($url,PHP_URL_PASS);
    }
    public function saveLocale(string $locale,array $items): void {
        if(!in_array($locale,['de','en'],true)||!$this->valid($items))throw new \InvalidArgumentException('Invalid menu.');
        $directory=$this->root.'/var/security';$fs=new Filesystem();$fs->mkdir($directory,0700);
        $lock=fopen($directory.'/menus.lock','c');if(!$lock||!flock($lock,LOCK_EX))throw new \RuntimeException('Cannot lock menu settings.');
        try{$menus=$this->read();$menus[$locale]=$items;$fs->dumpFile($directory.'/menus.json',json_encode($menus,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR));}
        finally{flock($lock,LOCK_UN);fclose($lock);}
    }
}
