<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Author;
use App\Entity\Category;
use App\Form\Type\AuthorAutocompleteType;
use App\Form\Type\CategoryAutocompleteType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\SearchType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Formulaire de recherche de livres
 *
 * Design Pattern : Search Form
 * Tous les champs sont optionnels pour permettre une recherche flexible
 */
class BookSearchType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('query', SearchType::class, [
                'label' => 'Rechercher',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Titre, ISBN, auteur...',
                    'autocomplete' => 'off',
                ],
            ])

            ->add('category', CategoryAutocompleteType::class, [
                'label' => 'Catégorie',
                'required' => false,
                'placeholder' => 'Toutes les catégories',
                'attr' => [
                    'class' => 'form-select',
                ],
            ])

            ->add('author', AuthorAutocompleteType::class, [
                'label' => 'Auteur',
                'required' => false,
                'placeholder' => 'Tous les auteurs',
                'attr' => [
                    'class' => 'form-select',
                ],
            ])

            ->add('availableOnly', CheckboxType::class, [
                'label' => 'Disponibles uniquement',
                'required' => false,
                'attr' => [
                    'class' => 'form-check-input',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'method' => 'GET',
            'csrf_protection' => false, // Désactivé pour les recherches GET
        ]);
    }
}
