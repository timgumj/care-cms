<?php
if(PHP_SAPI!=='cli')exit(1);
$root=dirname(__DIR__);$failed=0;
foreach(['demo.php','starters.php','ui.php','concurrency.php','verify-security.php','verify-user-management.php','verify-user-authorization.php'] as $test){passthru(escapeshellarg(PHP_BINARY).' '.escapeshellarg(__DIR__.'/'.$test),$code);if($code)$failed++;}
$files=[];foreach(['src','migrations','bin'] as $folder)foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root.'/'.$folder)) as $file)if($file->isFile()&&$file->getExtension()==='php')$files[]=$file->getPathname();
foreach($files as $file){exec(escapeshellarg(PHP_BINARY).' -l '.escapeshellarg($file).' 2>&1',$output,$code);if($code){echo implode("\n",$output)."\n";$failed++;}$output=[];}
echo count($files)." application/migration/script PHP files checked.\n";
exit($failed?1:0);
