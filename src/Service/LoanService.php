<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Book;
use App\Entity\Loan;
use App\Entity\User;
use App\Exception\LoanException;
use App\Repository\LoanRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Service Layer Pattern - Encapsule la logique métier complexe
 *
 * Avantages :
 * - Centralise la logique métier (Single Responsibility Principle)
 * - Réutilisable dans différents contrôleurs
 * - Testable unitairement
 * - Gère les transactions
 * - Facilite l'injection de dépendances
 */
class LoanService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private LoanRepository $loanRepository,
        private LoggerInterface $logger,
        private NotificationService $notificationService
    ) {}

    /**
     * Emprunte un livre
     *
     * Design Pattern : Facade
     * Simplifie une opération complexe en une seule méthode
     *
     * @throws LoanException Si l'emprunt n'est pas possible
     */
    public function borrowBook(User $user, Book $book): Loan
    {
        // Validation métier
        $this->validateBorrow($user, $book);

        // Début de la transaction
        $this->entityManager->beginTransaction();

        try {
            // Création de l'emprunt
            $loan = new Loan();
            $loan->setUser($user);
            $loan->setBook($book);

            // Mise à jour des copies disponibles
            $book->decrementAvailableCopies();

            // Persistence
            $this->entityManager->persist($loan);
            $this->entityManager->persist($book);
            $this->entityManager->flush();

            // Commit de la transaction
            $this->entityManager->commit();

            // Notification (Design Pattern : Observer)
            $this->notificationService->notifyLoanCreated($loan);

            // Logging
            $this->logger->info('Loan created', [
                'loan_id' => $loan->getId(),
                'user_id' => $user->getId(),
                'book_id' => $book->getId(),
            ]);

            return $loan;

        } catch (\Exception $e) {
            // Rollback en cas d'erreur
            $this->entityManager->rollback();

            $this->logger->error('Failed to create loan', [
                'error' => $e->getMessage(),
                'user_id' => $user->getId(),
                'book_id' => $book->getId(),
            ]);

            throw new LoanException('Impossible de créer l\'emprunt : ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Retourne un livre
     *
     * @throws LoanException Si le retour n'est pas possible
     */
    public function returnBook(Loan $loan): void
    {
        if ($loan->getReturnedAt() !== null) {
            throw new LoanException('Ce livre a déjà été retourné.');
        }

        $this->entityManager->beginTransaction();

        try {
            // Marquer comme retourné
            $loan->returnBook();

            // Persistence
            $this->entityManager->persist($loan);
            $this->entityManager->flush();

            $this->entityManager->commit();

            // Notification
            $this->notificationService->notifyLoanReturned($loan);

            $this->logger->info('Loan returned', [
                'loan_id' => $loan->getId(),
                'overdue_days' => $loan->getOverdueDays(),
            ]);

        } catch (\Exception $e) {
            $this->entityManager->rollback();

            $this->logger->error('Failed to return loan', [
                'error' => $e->getMessage(),
                'loan_id' => $loan->getId(),
            ]);

            throw new LoanException('Impossible de retourner le livre : ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Prolonge un emprunt
     *
     * @throws LoanException Si la prolongation n'est pas possible
     */
    public function extendLoan(Loan $loan, int $days = 14): void
    {
        if ($loan->getReturnedAt() !== null) {
            throw new LoanException('Impossible de prolonger un livre déjà retourné.');
        }

        if ($loan->isOverdue()) {
            throw new LoanException('Impossible de prolonger un emprunt en retard.');
        }

        // Extension de la date de retour
        $newDueDate = $loan->getDueDate()->modify("+$days days");
        $loan->setDueDate($newDueDate);

        $this->entityManager->flush();

        $this->notificationService->notifyLoanExtended($loan);

        $this->logger->info('Loan extended', [
            'loan_id' => $loan->getId(),
            'new_due_date' => $newDueDate->format('Y-m-d'),
        ]);
    }

    /**
     * Validation métier pour l'emprunt
     *
     * Design Pattern : Specification
     * Encapsule les règles métier de validation
     *
     * @throws LoanException Si la validation échoue
     */
    private function validateBorrow(User $user, Book $book): void
    {
        // Vérification : utilisateur actif
        if (!$user->isActive()) {
            throw new LoanException('Votre compte est désactivé. Impossible d\'emprunter des livres.');
        }

        // Vérification : limite d'emprunts atteinte
        if (!$user->canBorrow()) {
            throw new LoanException(
                sprintf(
                    'Vous avez atteint la limite de %d emprunts simultanés.',
                    $user->getMaxLoans()
                )
            );
        }

        // Vérification : livre disponible
        if (!$book->isAvailable()) {
            throw new LoanException('Ce livre n\'est plus disponible.');
        }

        // Vérification : l'utilisateur n'a pas déjà emprunté ce livre
        $existingLoan = $this->loanRepository->findActiveUserLoanForBook($user, $book);
        if ($existingLoan) {
            throw new LoanException('Vous avez déjà emprunté ce livre.');
        }
    }

    /**
     * Récupère les emprunts en retard
     *
     * @return Loan[]
     */
    public function getOverdueLoans(): array
    {
        return $this->loanRepository->findOverdueLoans();
    }

    /**
     * Récupère les emprunts actifs d'un utilisateur
     *
     * @return Loan[]
     */
    public function getUserActiveLoans(User $user): array
    {
        return $this->loanRepository->findBy([
            'user' => $user,
            'returnedAt' => null
        ], ['borrowedAt' => 'DESC']);
    }

    /**
     * Récupère l'historique complet d'un utilisateur
     *
     * @return Loan[]
     */
    public function getUserLoanHistory(User $user): array
    {
        return $this->loanRepository->findBy(
            ['user' => $user],
            ['borrowedAt' => 'DESC']
        );
    }

    /**
     * Statistiques des emprunts
     */
    public function getStatistics(): array
    {
        return [
            'active_loans' => $this->loanRepository->countActiveLoans(),
            'overdue_loans' => $this->loanRepository->countOverdueLoans(),
            'total_loans_this_month' => $this->loanRepository->countLoansThisMonth(),
        ];
    }

    /**
     * Traitement automatique des emprunts en retard
     *
     * Méthode appelée par une commande Symfony ou un cron
     */
    public function processOverdueLoans(): int
    {
        $overdueLoans = $this->getOverdueLoans();
        $count = 0;

        foreach ($overdueLoans as $loan) {
            try {
                // Envoyer une notification de rappel
                $this->notificationService->notifyOverdueReminder($loan);
                $count++;

                $this->logger->info('Overdue reminder sent', [
                    'loan_id' => $loan->getId(),
                    'overdue_days' => $loan->getOverdueDays(),
                ]);

            } catch (\Exception $e) {
                $this->logger->error('Failed to process overdue loan', [
                    'loan_id' => $loan->getId(),
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $count;
    }
}

// ===== Exception personnalisée =====
namespace App\Exception;

class LoanException extends \RuntimeException
{
}
