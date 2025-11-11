<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\DataTable\BookTableType;
use App\Repository\BookRepository;
use Kreyu\Bundle\DataTableBundle\DataTableFactoryAwareTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/books')]
class AdminBookController extends AbstractController
{
    use DataTableFactoryAwareTrait;

    #[Route('/', name: 'app_admin_books')]
    public function index(Request $request, BookRepository $bookRepository): Response
    {
        // Créer un QueryBuilder pour les livres
        $queryBuilder = $bookRepository->createQueryBuilder('b')
            ->leftJoin('b.author', 'a')
            ->leftJoin('b.category', 'c')
            ->addSelect('a', 'c');

        // Créer le DataTable avec le QueryBuilder
        $dataTable = $this->createDataTable(BookTableType::class, $queryBuilder);
        $dataTable->handleRequest($request);

        // Si c'est une requête d'export
        if ($dataTable->isExporting()) {
            return $this->file($dataTable->export());
        }

        // Rendre la vue normale
        return $this->render('admin/books/index.html.twig', [
            'books' => $dataTable->createView(),
        ]);
    }
}
