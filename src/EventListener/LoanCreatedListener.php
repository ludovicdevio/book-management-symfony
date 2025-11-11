<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Event\LoanCreatedEvent;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

/**
 * Event Listener - Réagit aux événements
 *
 * Design Pattern : Observer
 * Écoute les événements et exécute des actions
 */
#[AsEventListener(event: LoanCreatedEvent::NAME)]
class LoanCreatedListener
{
    public function __construct(
        private LoggerInterface $logger
    ) {}

    public function __invoke(LoanCreatedEvent $event): void
    {
        $loan = $event->getLoan();

        // Logging
        $this->logger->info('New loan created via event', [
            'loan_id' => $loan->getId(),
            'user_id' => $loan->getUser()->getId(),
            'book_id' => $loan->getBook()->getId(),
        ]);

        // Autres actions possibles :
        // - Envoyer une notification push
        // - Mettre à jour des statistiques
        // - Envoyer un webhook
        // - etc.
    }
}
