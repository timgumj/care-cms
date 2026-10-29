<?php
namespace App\Command;
use App\Entity\Page;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
#[AsCommand(name:'app:demo-seed',description:'Add fictional bilingual demo pages without replacing existing content or creating accounts.')]
final class DemoSeedCommand extends Command
{
 public function __construct(private EntityManagerInterface $em,#[Autowire('%env(DEFAULT_URI)%')] private string $baseUri){parent::__construct();}
 protected function execute(InputInterface $input,OutputInterface $output): int {
  $content=[
   ['home','/','de-AT','Raum für Ihr Wohlbefinden','Eine fiktive Praxis, ein echtes CMS.', '<main><section class="hero"><p class="eyebrow">CARE STUDIO · DEMO</p><h1>Raum für Ihr Wohlbefinden.</h1><p>Eine ruhige, zweisprachige Website für eine fiktive Gesundheitspraxis. Entdecken Sie Leistungen, Artikel und die Demo-Terminbuchung.</p><a class="button" href="/booking/?lang=de">Demo-Termin buchen</a></section><section class="cards"><article><h2>Persönliche Beratung</h2><p>Beispielinhalte, die Sie direkt im visuellen CMS bearbeiten können.</p></article><article><h2>Einfach organisieren</h2><p>Seiten, Artikel, Medien und Termine an einem Ort verwalten.</p></article><article><h2>Zwei Sprachen</h2><p>Deutsche und englische Inhalte gezielt verknüpfen.</p></article></section></main>'],
   ['home','/en/','en-AT','Space for your wellbeing','A fictional practice. A working content platform.', '<main><section class="hero"><p class="eyebrow">CARE STUDIO · DEMO</p><h1>Space for your wellbeing.</h1><p>A calm, bilingual website for a fictional health practice. Explore services, articles and a working demonstration of appointment booking.</p><a class="button" href="/booking/">Book a demo appointment</a></section><section class="cards"><article><h2>Personal support</h2><p>Example content you can edit directly in the visual CMS.</p></article><article><h2>Less administration</h2><p>Manage pages, articles, media and appointments in one place.</p></article><article><h2>Two languages</h2><p>Link German and English content while retaining independent editing.</p></article></section></main>'],
   ['services','/services/','de-AT','Unsere Leistungen','Beispielleistungen der fiktiven Praxis.','<main><section><h1>Unsere Leistungen</h1><h2>Beratung</h2><p>Ein 30-minütiger Demo-Termin für die Erstberatung.</p><h2>Folgetermin</h2><p>Ein 15-minütiger Demo-Termin. Diese Website bietet keine medizinischen Leistungen an.</p><a class="button" href="/booking/?lang=de">Verfügbarkeit ansehen</a></section></main>'],
   ['services','/en/services/','en-AT','Our services','Example services for a fictional practice.','<main><section><h1>Our services</h1><h2>Consultation</h2><p>A 30-minute demonstration appointment.</p><h2>Follow-up</h2><p>A 15-minute demonstration appointment. This website does not offer medical care.</p><a class="button" href="/booking/">View availability</a></section></main>'],
   ['about','/about/','de-AT','Über Care Studio','Ein unabhängiges Portfolio-Projekt.','<main><section><h1>Über Care Studio</h1><p>Care Studio ist eine erfundene Praxis. Dieses Portfolio zeigt ein Symfony CMS mit visueller Bearbeitung, Rollen und zweisprachigen Inhalten.</p><p>Keine echten Patientendaten eingeben.</p></section></main>'],
   ['about','/en/about/','en-AT','About Care Studio','An independent portfolio project.','<main><section><h1>About Care Studio</h1><p>Care Studio is a fictional practice. This portfolio demonstrates a Symfony CMS with visual editing, account roles and bilingual content.</p><p>Do not submit real patient information.</p></section></main>'],
   ['journal','/artikel/willkommen/','de-AT','Willkommen im Demo-Journal','Ein Beispielartikel für das CMS.','<h1>Willkommen im Demo-Journal</h1><p>Artikel lassen sich als Entwurf speichern, veröffentlichen und optional ins Englische übertragen.</p>'],
   ['journal','/en/articles/welcome/','en-AT','Welcome to the demo journal','An example CMS article.','<h1>Welcome to the demo journal</h1><p>Articles can be saved as drafts, published and optionally paired with an English translation.</p>'],
  ];
  $created=0;
  foreach($content as [$key,$path,$locale,$title,$description,$html]) {
   if($this->em->getRepository(Page::class)->findOneBy(['path'=>$path]))continue;
   $page=new Page();$page->translationKey=$key;$page->path=$path;$page->locale=$locale;$page->title=$title;$page->description=$description;$page->content=$html;$page->canonical=rtrim($this->baseUri,'/').$path;$page->kind=$key==='journal'?'Beitrag':'Seite';$page->headExtra='<meta name="viewport" content="width=device-width, initial-scale=1"><link rel="stylesheet" href="/assets/demo.css">';$this->em->persist($page);$created++;
  }
  $this->em->flush();$output->writeln('Added '.$created.' fictional pages/articles. No users or passwords were created.');return Command::SUCCESS;
 }
}
