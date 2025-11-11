<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Author;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\UX\Autocomplete\EntityAutocompleterInterface;

#[IsGranted('ROLE_USER')]
class AuthorAutocompleteController implements EntityAutocompleterInterface
{
    public function getEntityClass(): string
    {
        return Author::class;
    }

    public function createFilteredQueryBuilder(EntityRepository $repository, string $query): QueryBuilder
    {
        return $repository->createQueryBuilder('entity')
            ->andWhere('entity.firstName LIKE :search OR entity.lastName LIKE :search')
            ->setParameter('search', '%' . $query . '%')
            ->orderBy('entity.lastName', 'ASC')
            ->addOrderBy('entity.firstName', 'ASC');
    }

    public function getLabel(object $entity): string
    {
        /** @var Author $entity */
        return $entity->getFullName();
    }

    public function getValue(object $entity): mixed
    {
        /** @var Author $entity */
        return $entity->getId();
    }

    public function isGranted(Security $security): bool
    {
        return $security->isGranted('ROLE_USER');
    }
}
