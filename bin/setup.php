<?php
if(PHP_SAPI!=='cli')exit(1);
$root=dirname(__DIR__);chdir($root);
foreach(['pdo_sqlite','mbstring','dom','gd'] as $extension)if(!extension_loaded($extension))exit("Enable PHP extension: $extension\n");
if(!is_dir('vendor'))exit("Run composer install first.\n");
if(!is_file('.env.local'))file_put_contents('.env.local',"APP_SECRET=".bin2hex(random_bytes(32))."\n");
foreach(['cache','log','security/locks','security/rate-limits','sessions'] as $folder)if(!is_dir('var/'.$folder))mkdir('var/'.$folder,0700,true);
foreach(['doctrine:migrations:migrate --no-interaction','app:demo-seed','app:starter-default','assets:install public'] as $command){passthru(escapeshellarg(PHP_BINARY).' bin/console '.$command,$code);if($code)exit($code);}
echo "Setup complete. Run php bin/console app:create-admin your-email@example.test\nThen: php -S 127.0.0.1:8020 -t public bin/router.php\nMail inbox: http://127.0.0.1:8025 (docker compose up -d first)\n";
