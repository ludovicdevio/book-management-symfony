<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Loan;
use App\Entity\User;
use App\Entity\Book;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Loan>
 *
 * Repository pour gérer les emprunts de livres (Loan)
 */
class LoanRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Loan::class);
    }

    /**
     * Trouve tous les emprunts en cours (non retournés)
     *
     * @return Loan[]
     */
    public function findActiveLoans(): array
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.status = :status')
            ->setParameter('status', Loan::STATUS_ACTIVE)
            ->orderBy('l.dueDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve tous les emprunts en retard
     *
     * @return Loan[]
     */
    public function findOverdueLoans(): array
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.status = :status')
            ->setParameter('status', Loan::STATUS_OVERDUE)
            ->orderBy('l.dueDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère tous les emprunts d’un utilisateur donné
     *
     * @return Loan[]
     */
    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.user = :user')
            ->setParameter('user', $user)
            ->orderBy('l.borrowedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les emprunts actifs d'un utilisateur
     *
     * @return Loan[]
     */
    public function findActiveLoansByUser(User $user): array
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.user = :user')
            ->andWhere('l.status = :status')
            ->setParameter('user', $user)
            ->setParameter('status', Loan::STATUS_ACTIVE)
            ->orderBy('l.dueDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les emprunts en retard d'un utilisateur
     *
     * @return Loan[]
     */

    /**
     * Récupère les emprunts en retard d'un utilisateur
     *
     * @return Loan[]
     */
    public function findOverdueLoansByUser(User $user): array
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.user = :user')
            ->andWhere('l.status = :status')
            ->setParameter('user', $user)
            ->setParameter('status', Loan::STATUS_OVERDUE)
            ->orderBy('l.dueDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve un emprunt actif pour un livre donné
     */

    /**
     * Trouve un emprunt actif pour un livre donné
     */
    public function findActiveLoanForBook(Book $book): ?Loan
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.book = :book')
            ->andWhere('l.status = :status')
            ->setParameter('book', $book)
            ->setParameter('status', Loan::STATUS_ACTIVE)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Compte le nombre d'emprunts actifs
     */
    public function countActiveLoans(): int
    {
        return $this->count(['status' => Loan::STATUS_ACTIVE]);
    }

    /**
     * Compte le nombre d'emprunts actifs d'un utilisateur
     */
    public function countActiveLoansByUser(User $user): int
    {
        return $this->count([
            'user' => $user,
            'status' => Loan::STATUS_ACTIVE,
        ]);
    }

        /**
     * Compte le nombre d'emprunts en retard
     */
    public function countOverdueLoans(): int
    {
        return $this->count(['status' => Loan::STATUS_OVERDUE]);
    }

    /**
     * Compte le nombre d'emprunts en retard d'un utilisateur
     */
    public function countOverdueLoansByUser(User $user): int
    {
        return $this->count([
            'user' => $user,
            'status' => Loan::STATUS_OVERDUE,
        ]);
    }
}
