<?php
require __DIR__.'/starters.php';
use Symfony\Component\Console\Tester\CommandTester;
// Twig parses every template independently of the local Windows runtime's ACL probes.
$twig->registerUndefinedFunctionCallback(fn($name)=>new Twig\TwigFunction($name,fn()=>''));$twig->registerUndefinedFilterCallback(fn($name)=>new Twig\TwigFilter($name,fn($v)=>$v));
foreach($templates as $name=>$source)$twig->parse($twig->tokenize(new Twig\Source($source,$name)));
$seed=new CommandTester(new App\Command\DemoSeedCommand($em,'https://demo.example.test'));$seed->execute([]);
$content=new CommandTester(new App\Command\DemoContentCommand($em,$packs,'https://demo.example.test'));$content->execute([]);
foreach(['de-AT','en-AT'] as $locale){starterCheck($em->getRepository(App\Entity\Page::class)->count(['kind'=>'Beitrag','locale'=>$locale,'deletedAt'=>null])===8,'Expected eight bilingual journal entries');foreach(['privacy','terms','cookies','offers'] as $section){$page=$em->getRepository(App\Entity\Page::class)->findOneBy(['translationKey'=>'demo-'.$section,'locale'=>$locale]);starterCheck($page&&$page->published&&str_contains($page->content,'mint-'),'Missing styled editable policy/programme page');}}
$before=$em->getRepository(App\Entity\Page::class)->count([]);$content->execute([]);starterCheck($before===$em->getRepository(App\Entity\Page::class)->count([]),'Content upgrade duplicated records');starterCheck($em->find(App\Entity\Page::class,$originalId)->content==='<main>Original</main>','Content upgrade overwrote existing page');
$settingsRoot=sys_get_temp_dir().'/care-appearance-'.bin2hex(random_bytes(6));$settings=new App\Service\AppearanceSettings($settingsRoot);starterCheck($settings->mode()==='inline','Default desktop navigation wrong');$settings->save('hamburger');starterCheck($settings->mode()==='hamburger','Desktop mode did not persist');starterReject(fn()=>$settings->save('<script>'));starterCheck($settings->mode()==='hamburger','Invalid mode changed settings');unlink($settingsRoot.'/var/security/appearance.json');rmdir($settingsRoot.'/var/security');rmdir($settingsRoot.'/var');rmdir($settingsRoot);
starterCheck(str_contains((new App\Service\FontLibrary($db,$root))->css(),'Hanken Grotesk'),'Default font missing');
echo "PASS: all Twig templates parse; eight fictional articles per language; editable policies; idempotent content upgrade preserves existing edits; appearance settings reject invalid values; local Hanken font.\n";
