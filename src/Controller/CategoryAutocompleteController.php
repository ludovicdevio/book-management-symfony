<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Category;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\UX\Autocomplete\EntityAutocompleterInterface;

#[IsGranted('ROLE_USER')]
class CategoryAutocompleteController implements EntityAutocompleterInterface
{
    public function getEntityClass(): string
    {
        return Category::class;
    }

    public function createFilteredQueryBuilder(EntityRepository $repository, string $query): QueryBuilder
    {
        return $repository->createQueryBuilder('entity')
            ->andWhere('entity.name LIKE :search')
            ->setParameter('search', '%' . $query . '%')
            ->orderBy('entity.name', 'ASC');
    }

    public function getLabel(object $entity): string
    {
        /** @var Category $entity */
        return $entity->getName();
    }

    public function getValue(object $entity): mixed
    {
        /** @var Category $entity */
        return $entity->getId();
    }

    public function isGranted(Security $security): bool
    {
        return $security->isGranted('ROLE_USER');
    }
}
