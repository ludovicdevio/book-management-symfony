<?php

declare(strict_types=1);

namespace App\Exception;

/**
 * Exception personnalisée pour les erreurs liées aux emprunts
 *
 * Design Pattern : Exception Strategy
 * Permet de gérer spécifiquement les erreurs métier liées aux emprunts
 */
class LoanException extends \RuntimeException
{
}
