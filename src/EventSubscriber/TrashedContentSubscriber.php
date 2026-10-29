<?php
namespace App\EventSubscriber;
use App\Entity\Page;
use EasyCorp\Bundle\EasyAdminBundle\Event\BeforeCrudActionEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
final class TrashedContentSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array { return [BeforeCrudActionEvent::class=>'check']; }
    public function check(BeforeCrudActionEvent $event): void {
        $entity=$event->getAdminContext()?->getEntity()?->getInstance();
        if($entity instanceof Page && $entity->deletedAt) throw new NotFoundHttpException('Restore this content from Trash first.');
    }
}
