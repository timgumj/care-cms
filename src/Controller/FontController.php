<?php
namespace App\Controller;
use App\Service\FontLibrary;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{Response,BinaryFileResponse};
use Symfony\Component\Routing\Attribute\Route;
final class FontController extends AbstractController
{
 #[Route('/theme/fonts.css',name:'site_fonts_css',methods:['GET'])]
 public function styles(FontLibrary $fonts):Response{return new Response($fonts->css(),200,['Content-Type'=>'text/css; charset=UTF-8','Cache-Control'=>'public, max-age=0, must-revalidate']);}
 #[Route('/theme/fonts/{id}.woff2',name:'site_font_file',requirements:['id'=>'[a-f0-9]{64}'],methods:['GET'])]
 public function font(string $id,FontLibrary $fonts):Response{
  $path=$fonts->file($id);if(!$path)throw $this->createNotFoundException();
  return new BinaryFileResponse($path,200,['Content-Type'=>'font/woff2','X-Content-Type-Options'=>'nosniff','Cache-Control'=>'public, max-age=31536000, immutable']);
 }
}
