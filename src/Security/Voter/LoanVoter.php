<?php
declare(strict_types=1);

namespace App\Security\Voter;

use App\Entity\Loan;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Voter pour les emprunts
 */
class LoanVoter extends Voter
{
    public const VIEW = 'VIEW';
    public const RETURN = 'RETURN';
    public const EXTEND = 'EXTEND';

    protected function supports(string $attribute, mixed $subject): bool
    {
        if (!$subject instanceof Loan) {
            return false;
        }

        return in_array($attribute, [self::VIEW, self::RETURN, self::EXTEND]);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        /** @var Loan $loan */
        $loan = $subject;

        return match ($attribute) {
            self::VIEW => $this->canView($loan, $user),
            self::RETURN => $this->canReturn($loan, $user),
            self::EXTEND => $this->canExtend($loan, $user),
            default => false,
        };
    }

    /**
     * Un utilisateur peut voir son propre emprunt ou l'admin peut tout voir
     */
    private function canView(Loan $loan, User $user): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $loan->getUser() === $user;
    }

    /**
     * Un utilisateur peut retourner son propre emprunt
     * Un admin peut retourner n'importe quel emprunt
     */
    private function canReturn(Loan $loan, User $user): bool
    {
        // Ne peut pas retourner un livre déjà retourné
        if ($loan->getReturnedAt() !== null) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        return $loan->getUser() === $user;
    }

    /**
     * Un utilisateur peut prolonger son propre emprunt s'il n'est pas en retard
     */
    private function canExtend(Loan $loan, User $user): bool
    {
        // Ne peut pas prolonger un livre déjà retourné
        if ($loan->getReturnedAt() !== null) {
            return false;
        }

        // Ne peut pas prolonger un emprunt en retard
        if ($loan->isOverdue()) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        return $loan->getUser() === $user;
    }
}
