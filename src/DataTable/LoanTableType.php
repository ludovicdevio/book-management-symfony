<?php

declare(strict_types=1);

namespace App\DataTable;

use App\Entity\Loan;
use Kreyu\Bundle\DataTableBundle\Type\AbstractDataTableType;
use Kreyu\Bundle\DataTableBundle\DataTableBuilderInterface;
use Kreyu\Bundle\DataTableBundle\Column\Type\TextColumnType;
use Kreyu\Bundle\DataTableBundle\Column\Type\DateTimeColumnType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class LoanTableType extends AbstractDataTableType
{
    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
        private AuthorizationCheckerInterface $authorizationChecker,
        private CsrfTokenManagerInterface $csrfTokenManager
    ) {}

    public function buildDataTable(DataTableBuilderInterface $builder, array $options): void
    {
        $builder
            ->addColumn('user', TextColumnType::class, [
                'label' => 'Utilisateur',
                'sort' => true,
                'property_path' => 'user.fullName',
            ])
            ->addColumn('book', TextColumnType::class, [
                'label' => 'Livre',
                'sort' => true,
                'property_path' => 'book.title',
            ])
            ->addColumn('borrowedAt', DateTimeColumnType::class, [
                'label' => 'Date d\'emprunt',
                'sort' => true,
                'format' => 'd/m/Y H:i',
            ])
            ->addColumn('dueDate', DateTimeColumnType::class, [
                'label' => 'Date de retour prévue',
                'sort' => true,
                'format' => 'd/m/Y',
                'formatter' => function ($value, Loan $loan) {
                    $date = $value ? $value->format('d/m/Y') : '';
                    $class = $loan->isOverdue() ? ' class="text-danger fw-bold"' : '';
                    return sprintf('<span%s>%s</span>', $class, $date);
                },
            ])
            ->addColumn('returnedAt', DateTimeColumnType::class, [
                'label' => 'Date de retour effective',
                'format' => 'd/m/Y H:i',
                'formatter' => function ($value) {
                    return $value ? $value->format('d/m/Y H:i') : 'Non retourné';
                },
            ])
            ->addColumn('status', TextColumnType::class, [
                'label' => 'Statut',
                'formatter' => function ($value, Loan $loan) {
                    return match($loan->getStatus()) {
                        Loan::STATUS_ACTIVE => '<span class="badge bg-info">Actif</span>',
                        Loan::STATUS_RETURNED => '<span class="badge bg-success">Retourné</span>',
                        Loan::STATUS_OVERDUE => sprintf(
                            '<span class="badge bg-danger">En retard (%d jours)</span>',
                            $loan->getOverdueDays()
                        ),
                        default => '<span class="badge bg-secondary">Inconnu</span>',
                    };
                },
            ])
            ->addColumn('actions', TextColumnType::class, [
                'label' => 'Actions',
                'formatter' => function ($value, Loan $loan) {
                    if ($loan->getReturnedAt()) {
                        return '<span class="text-muted">-</span>';
                    }

                    $actions = '';

                    if ($this->authorizationChecker->isGranted('RETURN', $loan)) {
                        $actions .= sprintf(
                            '<form method="post" action="%s" style="display:inline;" onsubmit="return confirm(\'Confirmer le retour ?\')">'.
                            '<input type="hidden" name="_token" value="%s">'.
                            '<button type="submit" class="btn btn-sm btn-success">Retourner</button>'.
                            '</form> ',
                            $this->urlGenerator->generate('app_loan_return', ['id' => $loan->getId()]),
                            $this->csrfTokenManager->getToken('return'.$loan->getId())->getValue()
                        );
                    }

                    if ($this->authorizationChecker->isGranted('EXTEND', $loan)) {
                        $actions .= sprintf(
                            '<form method="post" action="%s" style="display:inline;" onsubmit="return confirm(\'Prolonger l\\\'emprunt ?\')">'.
                            '<input type="hidden" name="_token" value="%s">'.
                            '<button type="submit" class="btn btn-sm btn-warning">Prolonger</button>'.
                            '</form>',
                            $this->urlGenerator->generate('app_loan_extend', ['id' => $loan->getId()]),
                            $this->csrfTokenManager->getToken('extend'.$loan->getId())->getValue()
                        );
                    }

                    return $actions ?: '<span class="text-muted">-</span>';
                },
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Loan::class,
            'translation_domain' => 'messages',
        ]);
    }
}
