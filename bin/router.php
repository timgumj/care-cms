<?php
$public=realpath(__DIR__.'/../public');
$path=parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH);
$file=realpath($public.rawurldecode($path));
if($file&&str_starts_with($file,$public.DIRECTORY_SEPARATOR)&&is_file($file)&&!preg_match('~(?:^|/)\.|\.(?:php|phtml|phar)$~i',$path))return false;
require $public.'/index.php';
