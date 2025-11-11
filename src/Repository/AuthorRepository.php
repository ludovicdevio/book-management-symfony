<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Author;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Author>
 */
class AuthorRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Author::class);
    }

    /**
     * Trouve tous les auteurs triés par nom de famille (ordre alphabétique)
     *
     * @return Author[]
     */
    public function findAllOrderedByLastName(): array
    {
        return $this->createQueryBuilder('a')
            ->orderBy('a.lastName', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Recherche les auteurs par une partie du nom ou prénom
     *
     * @param string $term
     * @return Author[]
     */
    public function searchByName(string $term): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.firstName LIKE :term OR a.lastName LIKE :term')
            ->setParameter('term', '%' . $term . '%')
            ->orderBy('a.lastName', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Retourne les auteurs nés avant une certaine année
     *
     * @param int $year
     * @return Author[]
     */
    public function findBornBefore(int $year): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.birthYear < :year')
            ->setParameter('year', $year)
            ->orderBy('a.birthYear', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Compte le nombre total d'auteurs
     */
    public function countAll(): int
    {
        return (int) $this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }
}
