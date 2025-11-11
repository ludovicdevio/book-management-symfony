<?php
declare(strict_types=1);

namespace App\Security\Voter;

use App\Entity\Book;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Security Voter - Gestion fine des permissions
 *
 * Design Pattern : Strategy + Chain of Responsibility
 * Permet de définir des règles d'autorisation complexes et réutilisables
 *
 * Avantages :
 * - Logique d'autorisation centralisée
 * - Réutilisable dans contrôleurs et vues
 * - Testable unitairement
 * - Facilite l'ajout de nouvelles règles
 *
 * Utilisation dans un contrôleur :
 * $this->denyAccessUnlessGranted('EDIT', $book);
 *
 * Utilisation dans Twig :
 * {% if is_granted('EDIT', book) %}
 */
class BookVoter extends Voter
{
    // Constantes pour les permissions
    public const VIEW = 'VIEW';
    public const EDIT = 'EDIT';
    public const DELETE = 'DELETE';
    public const BORROW = 'BORROW';

    /**
     * Détermine si le voter peut gérer ce sujet et cet attribut
     */
    protected function supports(string $attribute, mixed $subject): bool
    {
        // Le voter gère uniquement les objets Book
        if (!$subject instanceof Book) {
            return false;
        }

        // Vérifier si l'attribut est supporté
        return in_array($attribute, [
            self::VIEW,
            self::EDIT,
            self::DELETE,
            self::BORROW,
        ]);
    }

    /**
     * Effectue le vote sur la permission
     */
    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        // Si l'utilisateur n'est pas connecté, refuser l'accès
        if (!$user instanceof User) {
            return false;
        }

        /** @var Book $book */
        $book = $subject;

        // Logique de vote selon l'attribut
        return match ($attribute) {
            self::VIEW => $this->canView($book, $user),
            self::EDIT => $this->canEdit($book, $user),
            self::DELETE => $this->canDelete($book, $user),
            self::BORROW => $this->canBorrow($book, $user),
            default => false,
        };
    }

    /**
     * Tout utilisateur connecté peut voir un livre
     */
    private function canView(Book $book, User $user): bool
    {
        return true;
    }

    /**
     * Seuls les admins peuvent modifier un livre
     */
    private function canEdit(Book $book, User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Seuls les admins peuvent supprimer un livre
     */
    private function canDelete(Book $book, User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Un utilisateur peut emprunter si :
     * - Le livre est disponible
     * - L'utilisateur est actif
     * - L'utilisateur n'a pas atteint sa limite d'emprunts
     */
    private function canBorrow(Book $book, User $user): bool
    {
        // Vérifier que le livre est disponible
        if (!$book->isAvailable()) {
            return false;
        }

        // Vérifier que l'utilisateur est actif
        if (!$user->isActive()) {
            return false;
        }

        // Vérifier que l'utilisateur n'a pas atteint sa limite
        if (!$user->canBorrow()) {
            return false;
        }

        return true;
    }
}
