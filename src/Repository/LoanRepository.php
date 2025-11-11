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
     * Trouve un emprunt actif pour un utilisateur et un livre donnés
     */
    public function findActiveUserLoanForBook(User $user, Book $book): ?Loan
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.user = :user')
            ->andWhere('l.book = :book')
            ->andWhere('l.status = :status')
            ->setParameter('user', $user)
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

    /**
     * Compte le nombre d'emprunts effectués ce mois-ci
     */
    public function countLoansThisMonth(): int
    {
        $startOfMonth = new \DateTime('first day of this month 00:00:00');
        $endOfMonth = new \DateTime('last day of this month 23:59:59');

        return (int) $this->createQueryBuilder('l')
            ->select('COUNT(l.id)')
            ->andWhere('l.borrowedAt >= :start')
            ->andWhere('l.borrowedAt <= :end')
            ->setParameter('start', $startOfMonth)
            ->setParameter('end', $endOfMonth)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Récupère le nombre d'emprunts par mois sur les N derniers mois
     *
     * @param int $months Nombre de mois à récupérer
     * @return array Format: ['labels' => ['Jan', 'Fév', ...], 'data' => [10, 15, ...]]
     */
    public function getLoansPerMonth(int $months = 12): array
    {
        $startDate = new \DateTime("first day of -{$months} months");

        $loans = $this->createQueryBuilder('l')
            ->andWhere('l.borrowedAt >= :start')
            ->setParameter('start', $startDate)
            ->orderBy('l.borrowedAt', 'ASC')
            ->getQuery()
            ->getResult();

        // Grouper les emprunts par mois en PHP
        $grouped = [];
        foreach ($loans as $loan) {
            $date = $loan->getBorrowedAt();
            $key = $date->format('Y-m');
            if (!isset($grouped[$key])) {
                $grouped[$key] = [
                    'year' => $date->format('Y'),
                    'month' => $date->format('n'),
                    'count' => 0
                ];
            }
            $grouped[$key]['count']++;
        }

        ksort($grouped);

        $labels = [];
        $data = [];
        $monthNames = ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin', 'Juil', 'Aoû', 'Sep', 'Oct', 'Nov', 'Déc'];

        foreach ($grouped as $result) {
            $monthIndex = (int)$result['month'] - 1;
            $labels[] = $monthNames[$monthIndex] . ' ' . $result['year'];
            $data[] = (int)$result['count'];
        }

        return [
            'labels' => $labels,
            'data' => $data,
        ];
    }

    /**
     * Récupère les catégories les plus empruntées
     *
     * @param int $limit Nombre de catégories à récupérer
     * @return array Format: ['labels' => ['Fiction', 'Science', ...], 'data' => [25, 18, ...]]
     */
    public function getMostBorrowedCategories(int $limit = 5): array
    {
        $loans = $this->createQueryBuilder('l')
            ->leftJoin('l.book', 'b')
            ->leftJoin('b.category', 'c')
            ->addSelect('b', 'c')
            ->getQuery()
            ->getResult();

        // Compter les emprunts par catégorie en PHP
        $categoryCounts = [];
        foreach ($loans as $loan) {
            $category = $loan->getBook()->getCategory();
            $categoryName = $category ? $category->getName() : 'Sans catégorie';

            if (!isset($categoryCounts[$categoryName])) {
                $categoryCounts[$categoryName] = 0;
            }
            $categoryCounts[$categoryName]++;
        }

        // Trier par ordre décroissant
        arsort($categoryCounts);

        // Limiter le nombre de résultats
        $categoryCounts = array_slice($categoryCounts, 0, $limit, true);

        $labels = [];
        $data = [];

        foreach ($categoryCounts as $categoryName => $count) {
            $labels[] = $categoryName;
            $data[] = $count;
        }

        return [
            'labels' => $labels,
            'data' => $data,
        ];
    }
}
