<?php
namespace App\Service;

use Symfony\Component\DependencyInjection\Attribute\Autowire;

final class MediaLibrary
{
    public function __construct(#[Autowire('%kernel.project_dir%')] private string $root) {}

    public function images(): array
    {
        $images=[];
        foreach (['optimized-images','demo-media','uploads/library','uploads/editor'] as $folder) {
            $directory=$this->root.'/public/'.$folder;
            if (!is_dir($directory)) continue;
            $realRoot=realpath($directory);
            foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS)) as $file) {
                if ($file->isLink() || !$file->isFile() || !in_array(strtolower($file->getExtension()), ['jpg','jpeg','png','webp','gif'], true)) continue;
                $real=$file->getRealPath();
                if (!$real || !str_starts_with($real,$realRoot.DIRECTORY_SEPARATOR)) continue;
                $info=@getimagesize($real);
                if (!$info) continue;
                $url='/'.$folder.'/'.str_replace('\\','/',substr($file->getPathname(),strlen($directory)+1));
                $images[]=['src'=>$url,'name'=>$file->getFilename(),'type'=>'image','width'=>$info[0],'height'=>$info[1],'bytes'=>$file->getSize(),'folder'=>$folder];
                if (count($images)>=2000) break 2;
            }
        }
        usort($images,fn($a,$b)=>strnatcasecmp($a['name'],$b['name']));
        return $images;
    }
}
