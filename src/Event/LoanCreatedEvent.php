<?php

declare(strict_types=1);

namespace App\Event;

use App\Entity\Loan;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Événement personnalisé pour la création d'un emprunt
 *
 * Design Pattern : Event
 * Encapsule les données de l'événement
 */
class LoanCreatedEvent extends Event
{
    public const NAME = 'loan.created';

    public function __construct(
        private Loan $loan
    ) {}

    public function getLoan(): Loan
    {
        return $this->loan;
    }
}
