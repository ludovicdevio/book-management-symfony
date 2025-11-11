<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Entity\Loan;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Events;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use App\Event\LoanCreatedEvent;

/**
 * Event Subscriber pour déclencher des événements personnalisés
 *
 * Design Pattern : Event-Driven Architecture
 * Découple complètement la logique métier des effets de bord
 */
#[AsDoctrineListener(event: Events::postPersist)]
class LoanEventSubscriber
{
    public function __construct(
        private EventDispatcherInterface $eventDispatcher
    ) {}

    public function postPersist(PostPersistEventArgs $args): void
    {
        $entity = $args->getObject();

        if (!$entity instanceof Loan) {
            return;
        }

        // Dispatch un événement personnalisé
        $event = new LoanCreatedEvent($entity);
        $this->eventDispatcher->dispatch($event, LoanCreatedEvent::NAME);
    }
}
