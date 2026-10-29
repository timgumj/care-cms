<?php
namespace App\Security;
use Symfony\Component\HtmlSanitizer\{HtmlSanitizer,HtmlSanitizerConfig};
final class EditorContentSanitizer
{
    public function sanitize(string $html): string {
        if(strlen($html)>2000000)throw new \InvalidArgumentException('Page content is too large.');
        // GrapesJS stores its generated CSS separately from element attributes.
        $styles=[];
        $html=preg_replace_callback('~<style\b[^>]*>(.*?)</style\s*>~is',function($m)use(&$styles){
            $css=str_replace('<','\\3c ',$m[1]);
            if(preg_match('~@import|expression\s*\(|javascript\s*:|behavior\s*:|-moz-binding~i',$css))throw new \InvalidArgumentException('Unsafe CSS is not allowed.');
            $styles[]='<style>'.$css.'</style>'; return '';
        },$html);
        $config=(new HtmlSanitizerConfig())->allowSafeElements()
            ->allowElement('main',['class','id'])->allowElement('style',[])
            ->allowAttribute('class','*')->allowAttribute('id','*')->allowAttribute('style','*')
            ->allowAttribute('fetchpriority',['img'])->allowAttribute('decoding',['img'])
            ->allowElement('iframe',['src','title','width','height','loading','allowfullscreen','referrerpolicy','style','class','id'])
            ->forceAttribute('iframe','sandbox','allow-scripts allow-same-origin allow-popups')
            ->allowMediaHosts(['www.google.com','maps.google.com'])->allowRelativeMedias()
            ->allowLinkSchemes(['https','http','mailto','tel'])->allowRelativeLinks()
            ->dropElement('script')->dropElement('object')->dropElement('embed')->dropElement('form')
            ->withMaxInputLength(2000000);
        $safe=(new HtmlSanitizer($config))->sanitize($html);
        $safe=preg_replace_callback('~<iframe\b[^>]*>.*?</iframe>~is',function($m){
            if(!preg_match('~\bsrc="([^"]+)"~i',$m[0],$src))return '';
            $url=parse_url(html_entity_decode($src[1],ENT_QUOTES|ENT_HTML5,'UTF-8'));
            return ($url['scheme']??'')==='https' && in_array($url['host']??'',['www.google.com','maps.google.com'],true) && str_starts_with($url['path']??'','/maps') ? $m[0] : '';
        },$safe);
        return $safe.implode('', $styles);
    }
}
