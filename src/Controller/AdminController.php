<?php
declare(strict_types=1);

namespace App\Controller;

use App\Repository\BookRepository;
use App\Repository\LoanRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Contrôleur Admin - Tableau de bord
 *
 * Design Pattern : Dashboard Pattern
 * Centralise les statistiques et métriques importantes
 */
#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
class AdminController extends AbstractController
{
    public function __construct(
        private BookRepository $bookRepository,
        private UserRepository $userRepository,
        private LoanRepository $loanRepository
    ) {}

    /**
     * Tableau de bord principal
     */
    #[Route('/', name: 'app_admin_dashboard')]
    public function dashboard(): Response
    {
        // Récupération des statistiques
        $stats = [
            'books' => $this->getBookStatistics(),
            'users' => $this->getUserStatistics(),
            'loans' => $this->getLoanStatistics(),
        ];

        // Données pour les graphiques
        $chartData = [
            'loansPerMonth' => $this->getLoansPerMonth(),
            'popularCategories' => $this->getPopularCategories(),
        ];

        return $this->render('admin/dashboard.html.twig', [
            'stats' => $stats,
            'chartData' => $chartData,
        ]);
    }

    /**
     * Statistiques des livres
     */
    private function getBookStatistics(): array
    {
        $bookStats = $this->bookRepository->getStatistics();

        return [
            'total' => $bookStats['total'],
            'available' => $bookStats['available'],
            'borrowed' => $bookStats['borrowed'],
            'availability_rate' => $bookStats['total'] > 0
                ? round(($bookStats['available'] / $bookStats['total']) * 100, 1)
                : 0,
        ];
    }

    /**
     * Statistiques des utilisateurs
     */
    private function getUserStatistics(): array
    {
        $totalUsers = $this->userRepository->count([]);
        $activeUsers = $this->userRepository->count(['isActive' => true]);

        return [
            'total' => $totalUsers,
            'active' => $activeUsers,
            'inactive' => $totalUsers - $activeUsers,
        ];
    }

    /**
     * Statistiques des emprunts
     */
    private function getLoanStatistics(): array
    {
        $activeLoans = $this->loanRepository->countActiveLoans();
        $overdueLoans = $this->loanRepository->countOverdueLoans();

        return [
            'active' => $activeLoans,
            'overdue' => $overdueLoans,
            'this_month' => $this->loanRepository->countLoansThisMonth(),
            'overdue_rate' => $activeLoans > 0
                ? round(($overdueLoans / $activeLoans) * 100, 1)
                : 0,
        ];
    }

    /**
     * Emprunts par mois (pour graphique)
     */
    private function getLoansPerMonth(): array
    {
        return $this->loanRepository->getLoansPerMonth(12);
    }

    /**
     * Catégories populaires (pour graphique)
     */
    private function getPopularCategories(): array
    {
        return $this->loanRepository->getMostBorrowedCategories(5);
    }
}
