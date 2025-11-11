<?php

declare(strict_types=1);

namespace App\Form;

use App\Controller\AuthorAutocompleteController;
use App\Controller\CategoryAutocompleteController;
use App\Entity\Author;
use App\Entity\Book;
use App\Entity\Category;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Image;
use Symfony\UX\Autocomplete\Form\AsEntityAutocompleteField;
use Symfony\UX\Autocomplete\Form\BaseEntityAutocompleteType;

/**
 * Formulaire de livre
 *
 * Design Pattern : Form Builder
 * Construction fluide du formulaire
 *
 * Utilise Symfony UX Autocomplete pour les relations Author et Category
 */
class BookType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Titre',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Entrez le titre du livre',
                ],
                'help' => 'Le titre complet du livre',
            ])

            ->add('isbn', TextType::class, [
                'label' => 'ISBN',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => '978-XXXXXXXXXX',
                    'pattern' => '[0-9-]+',
                ],
                'help' => 'ISBN à 10 ou 13 chiffres',
            ])

            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 5,
                    'placeholder' => 'Décrivez le livre...',
                ],
            ])

            ->add('publishedYear', IntegerType::class, [
                'label' => 'Année de publication',
                'attr' => [
                    'class' => 'form-control',
                    'min' => 1000,
                    'max' => date('Y') + 1,
                ],
            ])

            ->add('totalCopies', IntegerType::class, [
                'label' => 'Nombre total de copies',
                'attr' => [
                    'class' => 'form-control',
                    'min' => 1,
                ],
                'help' => 'Nombre d\'exemplaires disponibles dans la bibliothèque',
            ])

            ->add('availableCopies', IntegerType::class, [
                'label' => 'Copies disponibles',
                'attr' => [
                    'class' => 'form-control',
                    'readonly' => true,
                ],
                'help' => 'Mis à jour automatiquement',
                'disabled' => true,
            ])

            // Champ auteur avec autocomplétion
            ->add('author', BaseEntityAutocompleteType::class, [
                'class' => Author::class,
                'autocomplete_url' => 'author',
                'label' => 'Auteur',
                'placeholder' => 'Rechercher un auteur...',
                'tom_select_options' => [
                    'create' => false,
                    'maxItems' => 1,
                ],
            ])

            // Champ catégorie avec autocomplétion
            ->add('category', BaseEntityAutocompleteType::class, [
                'class' => Category::class,
                'autocomplete_url' => 'category',
                'label' => 'Catégorie',
                'placeholder' => 'Rechercher une catégorie...',
                'tom_select_options' => [
                    'create' => false,
                    'maxItems' => 1,
                ],
            ])

            ->add('coverImage', FileType::class, [
                'label' => 'Image de couverture',
                'required' => false,
                'mapped' => false,
                'attr' => [
                    'class' => 'form-control',
                    'accept' => 'image/*',
                ],
                'constraints' => [
                    new Image([
                        'maxSize' => '2M',
                        'mimeTypes' => ['image/jpeg', 'image/png', 'image/webp'],
                        'mimeTypesMessage' => 'Veuillez uploader une image valide (JPEG, PNG, WebP)',
                    ])
                ],
                'help' => 'Format accepté : JPEG, PNG, WebP. Taille max : 2MB',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Book::class,
        ]);
    }
}
