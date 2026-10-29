<?php
namespace App\Controller\Admin;
use App\Entity\Page;
use Symfony\Contracts\Translation\TranslatorInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\{Request,Response,JsonResponse};
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
#[IsGranted('ROLE_EDITOR')]
class EditorAssetsController extends AbstractController
{
    public function __construct(#[Autowire('%kernel.project_dir%')] private string $root, private \App\Security\UploadBudget $uploadBudget, #[Autowire(service: 'limiter.editor_upload')] private \Symfony\Component\RateLimiter\RateLimiterFactory $uploadLimiter) {}
    #[Route(\App\Security\AdminArea::PATH.'/editor/canvas-assets', name: 'admin_editor_canvas_assets', methods: ['GET'])]
    public function canvasAssets(Request $request, EntityManagerInterface $em): JsonResponse {
        $page=$request->query->getInt('page') ? $em->getRepository(Page::class)->find($request->query->getInt('page')) : null;
        $page ??= $em->getRepository(Page::class)->findOneBy(['path'=>'/']);
        $html=$page?->headExtra ?? '';
        $doc=new \DOMDocument();@$doc->loadHTML('<?xml encoding="UTF-8">'.$html);
        $styles=[];foreach($doc->getElementsByTagName('link') as $link)if($link->getAttribute('rel')==='stylesheet')$styles[]=$link->getAttribute('href');
        $css=[];foreach($doc->getElementsByTagName('style') as $style)$css[]=$style->textContent;
        return $this->json(['styles'=>$styles,'css'=>implode("\n",$css)],200,['Cache-Control'=>'private, no-store']);
    }
    #[Route(\App\Security\AdminArea::PATH.'/editor/page-styles.css', name: 'admin_editor_styles', methods: ['GET'])]
    public function styles(): Response {
        return new Response(file_get_contents($this->root.'/public/assets/demo.css'),200,['Content-Type'=>'text/css; charset=UTF-8','Cache-Control'=>'private, no-store']);
    }
    #[Route(\App\Security\AdminArea::PATH.'/editor/media', name: 'admin_editor_media', methods: ['GET'])]
    public function media(\App\Service\MediaLibrary $library): JsonResponse {
        return $this->json(['data'=>$library->images()],200,['Cache-Control'=>'private, no-store']);
    }
    #[Route(\App\Security\AdminArea::PATH.'/editor/upload', name: 'admin_editor_upload', methods: ['POST'])]
    public function upload(Request $request, TranslatorInterface $translator): JsonResponse {
        if(!$this->isCsrfTokenValid('editor_upload',$request->headers->get('X-CSRF-Token')))return $this->json(['error'=>$translator->trans('Die Sitzung ist abgelaufen. Bitte laden Sie die Seite neu.')],403);
        if(!$this->uploadLimiter->create((string)$this->getUser()->getUserIdentifier())->consume()->isAccepted())return $this->json(['error'=>$translator->trans('Too many image uploads. Please try again later.')],429);
        if(!extension_loaded('gd'))return $this->json(['error'=>$translator->trans('Image processing is unavailable. Please enable the PHP GD extension.')],503);
        $files=$request->files->all('files');
        if(!$files || count($files)>10)return $this->json(['error'=>$translator->trans('Bitte wählen Sie 1 bis 10 Bilder aus.')],422);
        $allowed=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp','image/gif'=>'gif'];$ready=[];
        foreach($files as $file){
            if(!$file instanceof UploadedFile || !$file->isValid() || $file->getSize()>5*1024*1024)return $this->json(['error'=>$translator->trans('Ein Bild darf höchstens 5 MB groß sein.')],422);
            $mime=$file->getMimeType();$info=@getimagesize($file->getPathname());
            if(!isset($allowed[$mime]) || !$info || $info[0]>12000 || $info[1]>12000 || $info[0]*$info[1]>16000000)return $this->json(['error'=>$translator->trans('Erlaubt sind JPG, PNG, WebP und GIF bis 12.000 × 12.000 Pixel.')],422);
            $ready[]=[$file,$allowed[$mime],$info];
        }
        $dest=$this->root.'/public/uploads/editor';if(!is_dir($dest))mkdir($dest,0755,true);
        try{return $this->uploadBudget->run($dest,count($ready),function()use($dest,$ready,$translator){
        $assets=[];
        foreach($ready as [$file,$extension,$info]){
            $name=bin2hex(random_bytes(16)).'.'.$extension;$image=@imagecreatefromstring(file_get_contents($file->getPathname()));
            if(!$image)return $this->json(['error'=>$translator->trans('The image could not be processed.')],422);
            if(in_array($extension,['png','webp','gif'],true)){imagealphablending($image,false);imagesavealpha($image,true);}
            $saved=match($extension){'jpg'=>imagejpeg($image,$dest.'/'.$name,90),'png'=>imagepng($image,$dest.'/'.$name),'webp'=>imagewebp($image,$dest.'/'.$name,90),'gif'=>imagegif($image,$dest.'/'.$name)};
            imagedestroy($image);
            if($saved&&filesize($dest.'/'.$name)>5*1024*1024){unlink($dest.'/'.$name);return $this->json(['error'=>$translator->trans('The processed image exceeds the 5 MB limit.')],422);}
            if(!$saved)throw new \RuntimeException('Could not save processed image.');
            $assets[]=['src'=>'/uploads/editor/'.$name,'type'=>'image','name'=>mb_substr(basename($file->getClientOriginalName()),0,160),'width'=>$info[0],'height'=>$info[1]];
        }
        return $this->json(['data'=>$assets],201,['Cache-Control'=>'no-store']);
        });}catch(\LengthException){return $this->json(['error'=>$translator->trans('Image storage is full. Contact the site administrator.')],422);}
    }
}
