<?php

namespace App\EventSubscriber;

use App\Entity\Category;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use Symfony\Component\String\Slugger\SluggerInterface;

/**
 * Event Subscriber pour générer automatiquement les slugs
 *
 * Design Pattern : Observer
 * S'abonne aux événements Doctrine pour réagir automatiquement
 *
 * Avantages :
 * - Centralisation de la logique métier
 * - Découplage (pas de dépendance dans les contrôleurs)
 * - Automatisation (aucune intervention manuelle)
 * - Réutilisable pour toutes les entités
 */
#[AsDoctrineListener(event: Events::prePersist)]
#[AsDoctrineListener(event: Events::preUpdate)]
class CategorySlugSubscriber
{
    public function __construct(
        private SluggerInterface $slugger
    ) {}

    /**
     * Génère le slug avant la création
     */
    public function prePersist(PrePersistEventArgs $args): void
    {
        $entity = $args->getObject();

        if (!$entity instanceof Category) {
            return;
        }

        $this->generateSlug($entity);
    }

    /**
     * Génère le slug avant la mise à jour
     */
    public function preUpdate(PreUpdateEventArgs $args): void
    {
        $entity = $args->getObject();

        if (!$entity instanceof Category) {
            return;
        }

        // Vérifie si le nom a changé pour régénérer le slug
        if ($args->hasChangedField('name')) {
            $slug = $this->slugger->slug($entity->getName())->lower();
            $entity->setSlug($slug);
        }
    }

    /**
     * Logique de génération du slug
     */
    private function generateSlug(Category $category): void
    {
        if (!$category->getSlug() || empty($category->getSlug())) {
            $slug = $this->slugger->slug($category->getName())->lower();
            $category->setSlug($slug);
        }
    }
}
