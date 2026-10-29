<?php
namespace App\Service;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Filesystem;

final class AnnouncementSettings
{
    public function __construct(#[Autowire('%kernel.project_dir%')] private string $root) {}
    public function defaults(): array {
        $result=[];
        foreach(['de','en'] as $locale)$result[$locale]=['enabled'=>false,'title'=>$locale==='de'?'Ankündigung':'Announcement','text'=>$locale==='de'?'Weihnachtsurlaub':'Christmas holiday','link_label'=>$locale==='de'?'Mehr erfahren':'Read more','mode'=>'anchor','target'=>'#weihnachtsurlaub','popup_text'=>''];
        return $result;
    }
    public function read(): array {
        $path=$this->root.'/var/security/announcements.json';
        if(!is_file($path))return $this->defaults();
        $values=json_decode((string)file_get_contents($path),true);
        return is_array($values)&&$this->valid($values)?$values:$this->defaults();
    }
    public function valid(array $values): bool {
        foreach(['de','en'] as $locale){
            $item=$values[$locale]??null;if(!is_array($item)||!isset($item['enabled'])||!is_bool($item['enabled']))return false;
            foreach(['title'=>80,'text'=>300,'link_label'=>80,'target'=>500,'popup_text'=>5000] as $key=>$max)
                if(!isset($item[$key])||!is_string($item[$key])||mb_strlen($item[$key])>$max||str_contains($item[$key],"\0"))return false;
            if(!in_array($item['mode']??null,['anchor','link','popup'],true))return false;
            if(!$item['enabled'])continue;
            if(trim($item['title'])===''||trim($item['text'])===''||trim($item['link_label'])==='')return false;
            if($item['mode']==='anchor'&&!preg_match('/^#[A-Za-z][A-Za-z0-9_-]*$/D',$item['target']))return false;
            if($item['mode']==='link'&&!$this->safeLink($item['target']))return false;
            if($item['mode']==='popup'&&trim($item['popup_text'])==='')return false;
        }
        return true;
    }
    private function safeLink(string $target): bool {
        if(preg_match('/[\x00-\x20\\\\]/',$target))return false;
        if(str_starts_with($target,'/')&&!str_starts_with($target,'//'))return true;
        return filter_var($target,FILTER_VALIDATE_URL)!==false&&strtolower((string)parse_url($target,PHP_URL_SCHEME))==='https'&&!parse_url($target,PHP_URL_USER)&&!parse_url($target,PHP_URL_PASS);
    }
    public function save(array $values): void {
        if(!$this->valid($values))throw new \InvalidArgumentException('Invalid announcement settings.');
        $directory=$this->root.'/var/security';$fs=new Filesystem();$fs->mkdir($directory,0700);
        $fs->dumpFile($directory.'/announcements.json',json_encode($values,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR));
    }
}
