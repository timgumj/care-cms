<?php
require __DIR__.'/../vendor/autoload.php';
$root=dirname(__DIR__).'/var/router-test-'.bin2hex(random_bytes(6));
mkdir($root.'/public',0700,true);mkdir($root.'/bin',0700,true);
copy(__DIR__.'/../bin/router.php',$root.'/bin/router.php');
file_put_contents($root.'/public/index.php','<?php return static fn()=>"front controller";');
file_put_contents($root.'/public/image.webp','test image');
file_put_contents($root.'/public/.env','must not be served');
file_put_contents($root.'/public/executable.php','must not be served');
try {
 foreach(['/','/sitemap.xml','/booking/','/.env','/executable.php'] as $uri){
  $_SERVER['REQUEST_URI']=$uri;$_SERVER['SCRIPT_FILENAME']=$root.'/bin/router.php';
  $result=require $root.'/bin/router.php';
  if(!$result instanceof Closure||$result()!=='front controller'||$_SERVER['SCRIPT_FILENAME']!==realpath($root.'/public').'/index.php'||$_SERVER['SCRIPT_NAME']!=='/index.php')throw new RuntimeException('Development router did not return the front controller for '.$uri);
 }
 $_SERVER['REQUEST_URI']='/image.webp';
 if((require $root.'/bin/router.php')!==false)throw new RuntimeException('Static image was not served as a file');
 echo "PASS: CLI router returns the Symfony front controller for pages, XML, hidden and executable paths; safe static assets remain directly served.\n";
}finally{
 foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root,FilesystemIterator::SKIP_DOTS),RecursiveIteratorIterator::CHILD_FIRST) as $file){if($file->isDir())rmdir($file->getPathname());else unlink($file->getPathname());}rmdir($root);
}
