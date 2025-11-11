<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Delete;
use App\Repository\BookRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * Entité Book - Représente un livre dans la bibliothèque
 *
 * Design Pattern utilisé : Entity (DDD - Domain-Driven Design)
 * Cette classe est un modèle riche qui encapsule la logique métier
 */
#[ORM\Entity(repositoryClass: BookRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[ApiResource(
    operations: [
        new Get(normalizationContext: ['groups' => ['book:read']]),
        new GetCollection(normalizationContext: ['groups' => ['book:read']]),
        new Post(denormalizationContext: ['groups' => ['book:write']]),
        new Put(denormalizationContext: ['groups' => ['book:write']]),
        new Delete()
    ],
    paginationEnabled: true,
    paginationItemsPerPage: 10
)]
class Book
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['book:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le titre est obligatoire')]
    #[Assert\Length(
        min: 2,
        max: 255,
        minMessage: 'Le titre doit contenir au moins {{ limit }} caractères',
        maxMessage: 'Le titre ne peut pas dépasser {{ limit }} caractères'
    )]
    #[Groups(['book:read', 'book:write'])]
    private ?string $title = null;

    #[ORM\Column(length: 13, unique: true)]
    #[Assert\NotBlank(message: 'L\'ISBN est obligatoire')]
    #[Assert\Isbn(message: 'L\'ISBN n\'est pas valide')]
    #[Groups(['book:read', 'book:write'])]
    private ?string $isbn = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['book:read', 'book:write'])]
    private ?string $description = null;

    #[ORM\Column]
    #[Assert\NotBlank(message: 'L\'année de publication est obligatoire')]
    #[Assert\Range(
        min: 1000,
        max: 2100,
        notInRangeMessage: 'L\'année doit être entre {{ min }} et {{ max }}'
    )]
    #[Groups(['book:read', 'book:write'])]
    private ?int $publishedYear = null;

    #[ORM\Column]
    #[Assert\NotBlank(message: 'Le nombre de copies est obligatoire')]
    #[Assert\PositiveOrZero(message: 'Le nombre de copies doit être positif ou zéro')]
    #[Groups(['book:read', 'book:write'])]
    private ?int $totalCopies = 0;

    #[ORM\Column]
    #[Groups(['book:read'])]
    private ?int $availableCopies = 0;

    #[ORM\Column(length: 500, nullable: true)]
    #[Groups(['book:read', 'book:write'])]
    private ?string $coverImage = null;

    #[ORM\ManyToOne(inversedBy: 'books')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: 'L\'auteur est obligatoire')]
    #[Groups(['book:read', 'book:write'])]
    private ?Author $author = null;

    #[ORM\ManyToOne(inversedBy: 'books')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: 'La catégorie est obligatoire')]
    #[Groups(['book:read', 'book:write'])]
    private ?Category $category = null;

    /**
     * @var Collection<int, Loan>
     */
    #[ORM\OneToMany(targetEntity: Loan::class, mappedBy: 'book', orphanRemoval: true)]
    private Collection $loans;

    #[ORM\Column]
    #[Groups(['book:read'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    #[Groups(['book:read'])]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->loans = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    /**
     * Lifecycle callback - Appelé automatiquement avant la mise à jour
     * Design Pattern: Observer (via Doctrine Events)
     */
    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    /**
     * Lifecycle callback - Initialise les copies disponibles
     */
    #[ORM\PrePersist]
    public function initializeAvailableCopies(): void
    {
        if ($this->availableCopies === 0 && $this->totalCopies > 0) {
            $this->availableCopies = $this->totalCopies;
        }
    }

    // Getters et Setters

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;
        return $this;
    }

    public function getIsbn(): ?string
    {
        return $this->isbn;
    }

    public function setIsbn(string $isbn): static
    {
        $this->isbn = $isbn;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getPublishedYear(): ?int
    {
        return $this->publishedYear;
    }

    public function setPublishedYear(int $publishedYear): static
    {
        $this->publishedYear = $publishedYear;
        return $this;
    }

    public function getTotalCopies(): ?int
    {
        return $this->totalCopies;
    }

    public function setTotalCopies(int $totalCopies): static
    {
        $this->totalCopies = $totalCopies;
        return $this;
    }

    public function getAvailableCopies(): ?int
    {
        return $this->availableCopies;
    }

    public function setAvailableCopies(int $availableCopies): static
    {
        $this->availableCopies = $availableCopies;
        return $this;
    }

    public function getCoverImage(): ?string
    {
        return $this->coverImage;
    }

    public function setCoverImage(?string $coverImage): static
    {
        $this->coverImage = $coverImage;
        return $this;
    }

    public function getAuthor(): ?Author
    {
        return $this->author;
    }

    public function setAuthor(?Author $author): static
    {
        $this->author = $author;
        return $this;
    }

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setCategory(?Category $category): static
    {
        $this->category = $category;
        return $this;
    }

    /**
     * @return Collection<int, Loan>
     */
    public function getLoans(): Collection
    {
        return $this->loans;
    }

    public function addLoan(Loan $loan): static
    {
        if (!$this->loans->contains($loan)) {
            $this->loans->add($loan);
            $loan->setBook($this);
        }

        return $this;
    }

    public function removeLoan(Loan $loan): static
    {
        if ($this->loans->removeElement($loan)) {
            if ($loan->getBook() === $this) {
                $loan->setBook(null);
            }
        }

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    /**
     * Logique métier : Vérifie si le livre est disponible
     */
    public function isAvailable(): bool
    {
        return $this->availableCopies > 0;
    }

    /**
     * Logique métier : Décrémenter les copies disponibles lors d'un emprunt
     */
    public function decrementAvailableCopies(): void
    {
        if ($this->availableCopies > 0) {
            $this->availableCopies--;
        }
    }

    /**
     * Logique métier : Incrémenter les copies disponibles lors d'un retour
     */
    public function incrementAvailableCopies(): void
    {
        if ($this->availableCopies < $this->totalCopies) {
            $this->availableCopies++;
        }
    }

    public function __toString(): string
    {
        return $this->title ?? '';
    }
}
