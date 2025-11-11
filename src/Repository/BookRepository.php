<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Book;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;

/**
 * Repository Pattern - Encapsule la logique de requêtes
 *
 * Avantages :
 * - Centralisation des requêtes complexes
 * - Réutilisabilité
 * - Testabilité (mockable)
 * - Séparation des préoccupations
 *
 * @extends ServiceEntityRepository<Book>
 */
class BookRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Book::class);
    }

    /**
     * Recherche avancée de livres
     *
     * Design Pattern : Query Object
     * Utilise QueryBuilder pour construire des requêtes complexes de manière fluide
     *
     * @return Book[]
     */
    public function searchBooks(
        ?string $query = null,
        ?int $categoryId = null,
        ?int $authorId = null,
        ?bool $availableOnly = false
    ): array {
        $qb = $this->createSearchQueryBuilder($query, $categoryId, $authorId, $availableOnly);

        return $qb->getQuery()->getResult();
    }

    /**
     * Query Builder réutilisable pour la recherche
     *
     * Design Pattern : Builder
     * Construit progressivement une requête complexe
     */
    public function createSearchQueryBuilder(
        ?string $query = null,
        ?int $categoryId = null,
        ?int $authorId = null,
        ?bool $availableOnly = false
    ): QueryBuilder {
        $qb = $this->createQueryBuilder('b')
            ->leftJoin('b.author', 'a')
            ->leftJoin('b.category', 'c')
            ->addSelect('a', 'c');

        // Recherche textuelle sur titre, ISBN ou auteur
        if ($query) {
            $qb->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->like('b.title', ':query'),
                    $qb->expr()->like('b.isbn', ':query'),
                    $qb->expr()->like('a.firstName', ':query'),
                    $qb->expr()->like('a.lastName', ':query')
                )
            )->setParameter('query', '%' . $query . '%');
        }

        // Filtrage par catégorie
        if ($categoryId) {
            $qb->andWhere('c.id = :categoryId')
               ->setParameter('categoryId', $categoryId);
        }

        // Filtrage par auteur
        if ($authorId) {
            $qb->andWhere('a.id = :authorId')
               ->setParameter('authorId', $authorId);
        }

        // Filtrage par disponibilité
        if ($availableOnly) {
            $qb->andWhere('b.availableCopies > 0');
        }

        $qb->orderBy('b.title', 'ASC');

        return $qb;
    }

    /**
     * Récupère les livres les plus populaires (les plus empruntés)
     *
     * Utilise une jointure avec comptage
     */
    public function findMostPopular(int $limit = 10): array
    {
        return $this->createQueryBuilder('b')
            ->leftJoin('b.loans', 'l')
            ->addSelect('COUNT(l.id) as HIDDEN loanCount')
            ->groupBy('b.id')
            ->orderBy('loanCount', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les nouveautés (livres récemment ajoutés)
     */
    public function findRecent(int $limit = 10): array
    {
        return $this->createQueryBuilder('b')
            ->leftJoin('b.author', 'a')
            ->leftJoin('b.category', 'c')
            ->addSelect('a', 'c')
            ->orderBy('b.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Compte les livres disponibles
     */
    public function countAvailableBooks(): int
    {
        return (int) $this->createQueryBuilder('b')
            ->select('COUNT(b.id)')
            ->where('b.availableCopies > 0')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Récupère les livres par catégorie avec pagination
     */
    public function findByCategory(int $categoryId, int $page = 1, int $limit = 12): array
    {
        $offset = ($page - 1) * $limit;

        return $this->createQueryBuilder('b')
            ->leftJoin('b.author', 'a')
            ->leftJoin('b.category', 'c')
            ->addSelect('a', 'c')
            ->where('c.id = :categoryId')
            ->setParameter('categoryId', $categoryId)
            ->orderBy('b.title', 'ASC')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Recherche de livres avec filtres multiples et tri personnalisé
     *
     * Design Pattern : Specification (implicite)
     * Permet de combiner plusieurs critères de recherche
     */
    public function findWithFilters(array $filters = [], string $sortBy = 'title', string $order = 'ASC'): array
    {
        $qb = $this->createQueryBuilder('b')
            ->leftJoin('b.author', 'a')
            ->leftJoin('b.category', 'c')
            ->addSelect('a', 'c');

        // Application dynamique des filtres
        foreach ($filters as $field => $value) {
            if ($value !== null && $value !== '') {
                $qb->andWhere("b.$field = :$field")
                   ->setParameter($field, $value);
            }
        }

        // Tri dynamique
        $allowedSortFields = ['title', 'publishedYear', 'createdAt'];
        if (in_array($sortBy, $allowedSortFields)) {
            $qb->orderBy("b.$sortBy", strtoupper($order));
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Statistiques des livres
     *
     * Retourne des données agrégées
     */
    public function getStatistics(): array
    {
        $qb = $this->createQueryBuilder('b');

        return [
            'total' => $qb->select('COUNT(b.id)')
                ->getQuery()
                ->getSingleScalarResult(),

            'available' => $this->createQueryBuilder('b2')
                ->select('COUNT(b2.id)')
                ->where('b2.availableCopies > 0')
                ->getQuery()
                ->getSingleScalarResult(),

            'borrowed' => $this->createQueryBuilder('b3')
                ->select('SUM(b3.totalCopies - b3.availableCopies)')
                ->getQuery()
                ->getSingleScalarResult() ?? 0,
        ];
    }

    /**
     * Recherche avec suggestion (pour l'autocomplete)
     *
     * Retourne des résultats limités pour l'autocomplétion
     */
    public function findForAutocomplete(string $query, int $limit = 10): array
    {
        return $this->createQueryBuilder('b')
            ->select('b.id', 'b.title', 'b.isbn')
            ->where('b.title LIKE :query OR b.isbn LIKE :query')
            ->setParameter('query', $query . '%')
            ->setMaxResults($limit)
            ->getQuery()
            ->getArrayResult();
    }

    /**
     * Sauvegarde une entité
     *
     * Méthode helper pour simplifier la persistence
     */
    public function save(Book $book, bool $flush = true): void
    {
        $this->getEntityManager()->persist($book);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Supprime une entité
     */
    public function remove(Book $book, bool $flush = true): void
    {
        $this->getEntityManager()->remove($book);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
