<?php
$public=realpath(__DIR__.'/../public');
$path=parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH);
$file=realpath($public.rawurldecode($path));
if($file&&str_starts_with($file,$public.DIRECTORY_SEPARATOR)&&is_file($file)&&!preg_match('~(?:^|/)\.|\.(?:php|phtml|phar)$~i',$path))return false;
// Symfony Runtime must re-enter the front controller, not this CLI router.
$_SERVER['SCRIPT_FILENAME']=$public.'/index.php';
$_SERVER['SCRIPT_NAME']='/index.php';
$_SERVER['PHP_SELF']='/index.php';
return require $public.'/index.php';
