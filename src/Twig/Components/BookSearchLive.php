<?php

namespace App\Twig\Components;

use App\Repository\BookRepository;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * Live Component - Recherche en temps réel
 *
 * Design Pattern : Observer (Reactive Programming)
 * Le composant se met à jour automatiquement quand les LiveProp changent
 *
 * Symfony UX Live Components permet de créer des interfaces réactives
 * sans écrire de JavaScript
 *
 * Avantages :
 * - Réactivité sans JavaScript custom
 * - Rendu côté serveur (SEO friendly)
 * - Validation automatique
 * - Moins de code à maintenir
 */
#[AsLiveComponent('book_search_live')]
class BookSearchLive
{
    use DefaultActionTrait;

    /**
     * LiveProp : Propriété réactive
     * Quand elle change, le composant se re-rend automatiquement
     */
    #[LiveProp(writable: true)]
    public string $query = '';

    #[LiveProp(writable: true)]
    public ?int $categoryId = null;

    #[LiveProp(writable: true)]
    public ?int $authorId = null;

    #[LiveProp(writable: true)]
    public bool $availableOnly = false;

    #[LiveProp]
    public int $limit = 12;

    public function __construct(
        private BookRepository $bookRepository
    ) {}

    /**
     * Méthode appelée automatiquement pour récupérer les résultats
     */
    public function getBooks(): array
    {
        if (strlen($this->query) < 2 && !$this->categoryId && !$this->authorId) {
            return $this->bookRepository->findRecent($this->limit);
        }

        return $this->bookRepository->searchBooks(
            $this->query ?: null,
            $this->categoryId,
            $this->authorId,
            $this->availableOnly
        );
    }

    /**
     * Compte le nombre de résultats
     */
    public function getResultCount(): int
    {
        return count($this->getBooks());
    }

    /**
     * Vérifie si une recherche est active
     */
    public function hasActiveSearch(): bool
    {
        return strlen($this->query) >= 2
            || $this->categoryId !== null
            || $this->authorId !== null
            || $this->availableOnly;
    }
}
