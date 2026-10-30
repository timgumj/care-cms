<?php
namespace App\Command;
use App\Entity\Page;
use App\Service\StarterPacks;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
#[AsCommand(name:'app:demo-content',description:'Add bilingual fictional journal articles and policy pages without replacing existing edits or accounts.')]
final class DemoContentCommand extends Command {
 public function __construct(private EntityManagerInterface $em,private StarterPacks $packs,#[Autowire('%env(DEFAULT_URI)%')] private string $baseUri){parent::__construct();}
 protected function execute(InputInterface $input,OutputInterface $output):int {
  $added=0;
  foreach(['de-AT','en-AT'] as $locale){$en=str_starts_with($locale,'en');
   foreach(['privacy','terms','cookies','offers'] as $section){$sample=$this->packs->sample('practice',$locale,$section);if($this->em->getRepository(Page::class)->findOneBy(['path'=>$sample->path]))continue;$sample->translationKey='demo-'.$section;$sample->published=true;$this->em->persist($sample);$added++;}
   $entries=[
    ['preparing-first-visit','Preparing for your first visit','Gut vorbereitet zum ersten Besuch','planning','A few practical details to help you plan a demonstration visit.','Praktische Hinweise zur Planung eines Demonstrationsbesuchs.'],
    ['inside-the-practice','A look inside the practice','Ein Blick in unsere Praxis','practice','Meet a welcoming space and the fictional people behind it.','Ein freundlicher Ort und die fiktiven Menschen dahinter.'],
    ['appointment-planning','Simple appointment planning','Termine einfach planen','planning','How the demonstration calendar, confirmations and cancellations work.','So funktionieren Demo-Kalender, Bestätigungen und Stornierungen.'],
    ['meet-the-team','The people behind the welcome','Die Menschen hinter dem Empfang','practice','A closer look at the roles that keep a practice organised.','Ein Einblick in die Aufgaben eines gut organisierten Praxisteams.'],
    ['follow-up-planning','Keeping your next steps organised','Die nächsten Schritte organisieren','planning','Clear information helps make the next conversation easier.','Klare Informationen erleichtern das nächste Gespräch.'],
    ['finding-your-way','Finding your way before a visit','Vor dem Besuch gut orientiert','planning','Opening hours, contact details and directions in one place.','Öffnungszeiten, Kontaktdaten und Anreise auf einen Blick.'],
    ['clear-conversations','Room for questions','Raum für Fragen','practice','Why we designed this fictional practice around clear conversations.','Warum verständliche Gespräche im Mittelpunkt dieser fiktiven Praxis stehen.']
   ];
   foreach($entries as [$slug,$titleEn,$titleDe,$category,$descEn,$descDe]){
    $path=($en?'/en/articles/':'/artikel/').$slug.'/';if($this->em->getRepository(Page::class)->findOneBy(['path'=>$path]))continue;
    $page=new Page();$page->path=$path;$page->locale=$locale;$page->translationKey='demo-journal-'.$slug;$page->kind='Beitrag';$page->title=$en?$titleEn:$titleDe;$page->description=$en?$descEn:$descDe;$page->canonical=rtrim($this->baseUri,'/').$path;
    $page->content=$en?'<p>'.$descEn.'</p><h2>A little planning goes a long way</h2><p>Our fictional practice brings practical information and appointment planning together. Before trying a demonstration visit, browse the services page, check the calendar and choose the appointment type that suits your test.</p><p>The website is designed to keep useful details close at hand: opening hours, contact information and a clear path back to the booking calendar. Everything shown is fictional and intended to demonstrate the CMS.</p><h2>What to expect</h2><p>A demonstration consultation lasts 30 minutes. A follow-up lasts 15 minutes. Available times appear in the booking form, and a confirmation is sent after a successful reservation. Use fictional contact information while evaluating the system.</p><h2>Keep the next step clear</h2><p>Your confirmation includes a cancellation link. The practice calendar helps administrators review bookings while editors manage pages and articles independently.</p><p>For a real practice, replace this article with information reviewed by the site owner. This example offers no medical advice or real healthcare services.</p>':'<p>'.$descDe.'</p><h2>Gute Planung macht vieles einfacher</h2><p>Unsere fiktive Praxis verbindet praktische Informationen mit einer übersichtlichen Terminplanung. Entdecken Sie vor einem Demonstrationsbesuch die Leistungen, öffnen Sie den Kalender und wählen Sie einen passenden Termintyp.</p><p>Öffnungszeiten, Kontaktdaten und der Weg zur Buchung sind leicht zu finden. Alle Angaben sind fiktiv und dienen der Demonstration des CMS.</p><h2>Was Sie erwartet</h2><p>Eine Demonstrationsberatung dauert 30 Minuten, ein Folgetermin 15 Minuten. Freie Zeiten werden im Buchungsformular angezeigt. Nach einer erfolgreichen Reservierung wird eine Bestätigung versendet. Verwenden Sie beim Testen fiktive Kontaktdaten.</p><h2>Der nächste Schritt bleibt klar</h2><p>Ihre Bestätigung enthält einen Stornierungslink. Administratoren verwalten Termine im Praxiskalender, während Redakteure Seiten und Artikel unabhängig bearbeiten können.</p><p>Für eine tatsächliche Praxis ist dieser Artikel durch geprüfte eigene Informationen zu ersetzen. Dieses Beispiel enthält keine medizinische Beratung und bietet keine tatsächliche Versorgung.</p>';
    $this->em->persist($page);$added++;
   }
  }
  $this->em->flush();$output->writeln('Added '.$added.' demo content items. Existing edits, users and bookings retained.');return Command::SUCCESS;
 }
}
