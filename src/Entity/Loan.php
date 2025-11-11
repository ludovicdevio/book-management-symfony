<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\LoanRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Entité Loan - Gestion des emprunts
 *
 * Design Pattern : Value Object (contient la logique d'emprunt)
 */
#[ORM\Entity(repositoryClass: LoanRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Loan
{
    public const STATUS_ACTIVE = 'active';
    public const STATUS_RETURNED = 'returned';
    public const STATUS_OVERDUE = 'overdue';

    public const DEFAULT_LOAN_DURATION_DAYS = 21; // 3 semaines

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'loans')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\ManyToOne(inversedBy: 'loans')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Book $book = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $borrowedAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $dueDate = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $returnedAt = null;

    #[ORM\Column(length: 20)]
    private ?string $status = self::STATUS_ACTIVE;

    public function __construct()
    {
        $this->borrowedAt = new \DateTimeImmutable();
        $this->dueDate = $this->borrowedAt->modify('+' . self::DEFAULT_LOAN_DURATION_DAYS . ' days');
        $this->status = self::STATUS_ACTIVE;
    }

    /**
     * Logique métier : Mise à jour automatique du statut
     */
    #[ORM\PreUpdate]
    #[ORM\PrePersist]
    public function updateStatus(): void
    {
        if ($this->returnedAt !== null) {
            $this->status = self::STATUS_RETURNED;
        } elseif ($this->isOverdue()) {
            $this->status = self::STATUS_OVERDUE;
        } else {
            $this->status = self::STATUS_ACTIVE;
        }
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;
        return $this;
    }

    public function getBook(): ?Book
    {
        return $this->book;
    }

    public function setBook(?Book $book): static
    {
        $this->book = $book;
        return $this;
    }

    public function getBorrowedAt(): ?\DateTimeImmutable
    {
        return $this->borrowedAt;
    }

    public function setBorrowedAt(\DateTimeImmutable $borrowedAt): static
    {
        $this->borrowedAt = $borrowedAt;
        return $this;
    }

    public function getDueDate(): ?\DateTimeImmutable
    {
        return $this->dueDate;
    }

    public function setDueDate(\DateTimeImmutable $dueDate): static
    {
        $this->dueDate = $dueDate;
        return $this;
    }

    public function getReturnedAt(): ?\DateTimeImmutable
    {
        return $this->returnedAt;
    }

    public function setReturnedAt(?\DateTimeImmutable $returnedAt): static
    {
        $this->returnedAt = $returnedAt;
        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;
        return $this;
    }

    /**
     * Logique métier : Vérifie si l'emprunt est en retard
     */
    public function isOverdue(): bool
    {
        if ($this->returnedAt !== null) {
            return false;
        }

        return new \DateTimeImmutable() > $this->dueDate;
    }

    /**
     * Logique métier : Calcule le nombre de jours de retard
     */
    public function getOverdueDays(): int
    {
        if (!$this->isOverdue()) {
            return 0;
        }

        $now = new \DateTimeImmutable();
        return $now->diff($this->dueDate)->days;
    }

    /**
     * Logique métier : Retourner le livre
     */
    public function returnBook(): void
    {
        $this->returnedAt = new \DateTimeImmutable();
        $this->status = self::STATUS_RETURNED;

        // Incrémenter les copies disponibles
        if ($this->book) {
            $this->book->incrementAvailableCopies();
        }
    }
}
