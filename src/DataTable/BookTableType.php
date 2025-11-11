<?php

declare(strict_types=1);
namespace App\DataTable;

use App\Entity\Book;
use Kreyu\Bundle\DataTableBundle\Type\AbstractDataTableType;
use Kreyu\Bundle\DataTableBundle\DataTableBuilderInterface;
use Kreyu\Bundle\DataTableBundle\Column\Type\TextColumnType;
use Kreyu\Bundle\DataTableBundle\Column\Type\NumberColumnType;
use Kreyu\Bundle\DataTableBundle\Column\Type\DateTimeColumnType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Configuration du DataTable pour les livres
 *
 * Kreyu DataTable Bundle permet de créer des tableaux de données
 * avec tri, filtrage et pagination automatiques
 *
 * Design Pattern : Builder + Factory
 */
class BookTableType extends AbstractDataTableType
{
    public function __construct(
        private UrlGeneratorInterface $urlGenerator
    ) {}

    /**
     * Configure les colonnes du tableau
     */
    public function buildDataTable(DataTableBuilderInterface $builder, array $options): void
    {
        $builder
            ->addColumn('title', TextColumnType::class, [
                'label' => 'Titre',
                'sort' => true,
            ])
            ->addColumn('author', TextColumnType::class, [
                'label' => 'Auteur',
                'sort' => true,
                'property_path' => 'author.fullName',
            ])
            ->addColumn('category', TextColumnType::class, [
                'label' => 'Catégorie',
                'sort' => true,
                'property_path' => 'category.name',
            ])
            ->addColumn('isbn', TextColumnType::class, [
                'label' => 'ISBN',
            ])
            ->addColumn('publishedYear', NumberColumnType::class, [
                'label' => 'Année',
                'sort' => true,
            ])
            ->addColumn('availableCopies', NumberColumnType::class, [
                'label' => 'Disponibles',
                'sort' => true,
                'formatter' => function ($value, Book $book) {
                    return sprintf('%d / %d', $value, $book->getTotalCopies());
                },
            ])
            ->addColumn('status', TextColumnType::class, [
                'label' => 'Statut',
                'formatter' => function ($value, Book $book) {
                    return $book->isAvailable()
                        ? '<span class="badge bg-success">Disponible</span>'
                        : '<span class="badge bg-danger">Indisponible</span>';
                },
            ])
            ->addColumn('createdAt', DateTimeColumnType::class, [
                'label' => 'Date d\'ajout',
                'sort' => true,
                'format' => 'd/m/Y',
            ])
            ->addColumn('actions', TextColumnType::class, [
                'label' => 'Actions',
                'formatter' => function ($value, Book $book) {
                    return sprintf(
                        '<a href="%s" class="btn btn-sm btn-primary">Voir</a> ' .
                        '<a href="%s" class="btn btn-sm btn-warning">Modifier</a>',
                        $this->urlGenerator->generate('app_book_show', ['id' => $book->getId()]),
                        $this->urlGenerator->generate('app_book_edit', ['id' => $book->getId()])
                    );
                },
            ]);
    }

    /**
     * Options du DataTable
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Book::class,
            'translation_domain' => 'messages',
        ]);
    }
}
