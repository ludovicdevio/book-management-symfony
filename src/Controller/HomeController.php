<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\BookRepository;
use App\Repository\UserRepository;
use App\Repository\LoanRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Contrôleur de la page d'accueil
 */
class HomeController extends AbstractController
{
    public function __construct(
        private BookRepository $bookRepository,
        private UserRepository $userRepository,
        private LoanRepository $loanRepository
    ) {}

    #[Route('/', name: 'app_home')]
    public function index(): Response
    {
        // Statistiques pour la page d'accueil
        $totalBooks = $this->bookRepository->count([]);
        $totalUsers = $this->userRepository->count(['isActive' => true]);
        $activeLoans = $this->loanRepository->countActiveLoans();

        $statistics = [
            'total_books' => $totalBooks,
            'total_users' => $totalUsers,
            'active_loans' => $activeLoans,
        ];

        // Livres récents
        $recentBooks = $this->bookRepository->findBy([], ['id' => 'DESC'], 6);
        $popularBooks = $this->bookRepository->findBy([], ['id' => 'DESC'], 6);

        return $this->render('home/index.html.twig', [
            'statistics' => $statistics,
            'recent_books' => $recentBooks,
            'popular_books' => $popularBooks,
        ]);
    }
}
