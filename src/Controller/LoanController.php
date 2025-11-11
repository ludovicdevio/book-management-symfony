<?php

namespace App\Controller;

use App\Entity\Book;
use App\Entity\Loan;
use App\Exception\LoanException;
use App\Repository\LoanRepository;
use App\Service\LoanService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Contrôleur de gestion des emprunts
 *
 * Utilise le Service Layer pour la logique métier complexe
 */
#[Route('/loans')]
#[IsGranted('ROLE_USER')]
class LoanController extends AbstractController
{
    public function __construct(
        private LoanService $loanService,
        private LoanRepository $loanRepository
    ) {}

    /**
     * Liste des emprunts de l'utilisateur connecté
     *
     * Design Pattern : Dependency Injection
     * Les services sont injectés automatiquement par Symfony
     */
    #[Route('/my-loans', name: 'app_loan_my_loans', methods: ['GET'])]
    public function myLoans(): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        $activeLoans = $this->loanService->getUserActiveLoans($user);
        $loanHistory = $this->loanService->getUserLoanHistory($user);

        return $this->render('loan/my_loans.html.twig', [
            'active_loans' => $activeLoans,
            'loan_history' => $loanHistory,
            'user' => $user,
        ]);
    }

    /**
     * Emprunter un livre
     *
     * Design Pattern : Command
     * L'action d'emprunt est encapsulée dans le service
     */
    #[Route('/borrow/{id}', name: 'app_loan_borrow', methods: ['POST'])]
    public function borrow(Book $book, Request $request): Response
    {
        // Vérification CSRF
        if (!$this->isCsrfTokenValid('borrow'.$book->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token de sécurité invalide.');
            return $this->redirectToRoute('app_book_show', ['id' => $book->getId()]);
        }

        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        try {
            // Délégation de la logique métier au service
            $loan = $this->loanService->borrowBook($user, $book);

            $this->addFlash('success', sprintf(
                'Le livre "%s" a été emprunté avec succès. Date de retour : %s',
                $book->getTitle(),
                $loan->getDueDate()->format('d/m/Y')
            ));

        } catch (LoanException $e) {
            // Gestion des exceptions métier
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('app_book_show', ['id' => $book->getId()]);
    }

    /**
     * Retourner un livre
     */
    #[Route('/{id}/return', name: 'app_loan_return', methods: ['POST'])]
    public function return(Loan $loan, Request $request): Response
    {
        // Vérification que l'emprunt appartient à l'utilisateur
        if ($loan->getUser() !== $this->getUser() && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas retourner cet emprunt.');
        }

        if (!$this->isCsrfTokenValid('return'.$loan->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token de sécurité invalide.');
            return $this->redirectToRoute('app_loan_my_loans');
        }

        try {
            $this->loanService->returnBook($loan);
            $this->addFlash('success', 'Le livre a été retourné avec succès.');

        } catch (LoanException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('app_loan_my_loans');
    }

    /**
     * Prolonger un emprunt
     */
    #[Route('/{id}/extend', name: 'app_loan_extend', methods: ['POST'])]
    public function extend(Loan $loan, Request $request): Response
    {
        if ($loan->getUser() !== $this->getUser() && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas prolonger cet emprunt.');
        }

        if (!$this->isCsrfTokenValid('extend'.$loan->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token de sécurité invalide.');
            return $this->redirectToRoute('app_loan_my_loans');
        }

        try {
            $this->loanService->extendLoan($loan);
            $this->addFlash('success', sprintf(
                'L\'emprunt a été prolongé jusqu\'au %s.',
                $loan->getDueDate()->format('d/m/Y')
            ));

        } catch (LoanException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('app_loan_my_loans');
    }

    /**
     * Liste de tous les emprunts (admin seulement)
     */
    #[Route('/admin/all', name: 'app_loan_admin_index', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function adminIndex(Request $request): Response
    {
        $status = $request->query->get('status');

        $queryBuilder = $this->loanRepository->createQueryBuilder('l')
            ->leftJoin('l.user', 'u')
            ->leftJoin('l.book', 'b')
            ->addSelect('u', 'b')
            ->orderBy('l.borrowedAt', 'DESC');

        if ($status) {
            $queryBuilder->andWhere('l.status = :status')
                ->setParameter('status', $status);
        }

        $loans = $queryBuilder->getQuery()->getResult();
        $statistics = $this->loanService->getStatistics();

        return $this->render('loan/admin_index.html.twig', [
            'loans' => $loans,
            'statistics' => $statistics,
            'current_status' => $status,
        ]);
    }

    /**
     * Liste des emprunts en retard (admin)
     */
    #[Route('/admin/overdue', name: 'app_loan_admin_overdue', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function adminOverdue(): Response
    {
        $overdueLoans = $this->loanService->getOverdueLoans();

        return $this->render('loan/admin_overdue.html.twig', [
            'overdue_loans' => $overdueLoans,
        ]);
    }
}
