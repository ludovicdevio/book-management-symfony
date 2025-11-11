<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Book;
use App\Form\BookType;
use App\Form\BookSearchType;
use App\Repository\BookRepository;
use App\Repository\CategoryRepository;
use App\Repository\AuthorRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Contrôleur de gestion des livres
 *
 * Design Pattern : Front Controller (Symfony)
 * Toutes les requêtes passent par le routing Symfony
 *
 * Convention de nommage :
 * - index : liste
 * - show : affichage détaillé
 * - new : création
 * - edit : modification
 * - delete : suppression
 */
#[Route('/books')]
class BookController extends AbstractController
{
    public function __construct(
        private BookRepository $bookRepository,
        private CategoryRepository $categoryRepository,
        private AuthorRepository $authorRepository,
        private EntityManagerInterface $entityManager,
        private PaginatorInterface $paginator
    ) {}

    /**
     * Liste des livres avec recherche
     *
     * Design Pattern : MVC (Model-View-Controller)
     * Le contrôleur orchestre les interactions entre le modèle et la vue
     */
    #[Route('/', name: 'app_book_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        // Création du formulaire de recherche
        $searchForm = $this->createForm(BookSearchType::class);
        $searchForm->handleRequest($request);

        // Récupération des paramètres de recherche
        $query = $request->query->get('q');
        $categoryId = $request->query->get('category');
        $authorId = $request->query->get('author');
        $availableOnly = $request->query->getBoolean('available');

        // Appel au repository avec QueryBuilder pour la pagination
        if ($searchForm->isSubmitted() && $searchForm->isValid()) {
            $data = $searchForm->getData();
            $queryBuilder = $this->bookRepository->createSearchQueryBuilder(
                $data['query'] ?? null,
                $data['category']?->getId(),
                $data['author']?->getId(),
                $data['availableOnly'] ?? false
            );
        } else {
            $queryBuilder = $this->bookRepository->createSearchQueryBuilder($query, $categoryId, $authorId, $availableOnly);
        }

        // Pagination avec KnpPaginator
        $pagination = $this->paginator->paginate(
            $queryBuilder,
            $request->query->getInt('page', 1),
            12 // Nombre d'éléments par page
        );

        // Récupération des statistiques
        $statistics = $this->bookRepository->getStatistics();

        return $this->render('book/index.html.twig', [
            'books' => $pagination,
            'search_form' => $searchForm,
            'statistics' => $statistics,
        ]);
    }

    /**
     * Affichage détaillé d'un livre
     */
    #[Route('/{id}', name: 'app_book_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(Book $book): Response
    {
        // Design Pattern : ParamConverter
        // Symfony convertit automatiquement l'id en entité Book

        return $this->render('book/show.html.twig', [
            'book' => $book,
        ]);
    }

    /**
     * Création d'un nouveau livre
     *
     * Design Pattern : Form Object (Symfony Forms)
     */
    #[Route('/new', name: 'app_book_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function new(Request $request): Response
    {
        $book = new Book();
        $form = $this->createForm(BookType::class, $book);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Persistence via le repository
            $this->bookRepository->save($book, true);

            // Flash message (Design Pattern : Session Flash)
            $this->addFlash('success', 'Le livre a été créé avec succès.');

            // Redirection (Pattern Post/Redirect/Get - PRG)
            return $this->redirectToRoute('app_book_show', ['id' => $book->getId()]);
        }

        return $this->render('book/new.html.twig', [
            'book' => $book,
            'form' => $form,
        ]);
    }

    /**
     * Modification d'un livre
     */
    #[Route('/{id}/edit', name: 'app_book_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function edit(Request $request, Book $book): Response
    {
        $form = $this->createForm(BookType::class, $book);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();

            $this->addFlash('success', 'Le livre a été modifié avec succès.');

            return $this->redirectToRoute('app_book_show', ['id' => $book->getId()]);
        }

        return $this->render('book/edit.html.twig', [
            'book' => $book,
            'form' => $form,
        ]);
    }

    /**
     * Suppression d'un livre
     *
     * Design Pattern : CSRF Protection
     * Protection contre les attaques Cross-Site Request Forgery
     */
    #[Route('/{id}', name: 'app_book_delete', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(Request $request, Book $book): Response
    {
        // Vérification du token CSRF
        if ($this->isCsrfTokenValid('delete'.$book->getId(), $request->request->get('_token'))) {
            $this->bookRepository->remove($book, true);
            $this->addFlash('success', 'Le livre a été supprimé avec succès.');
        }

        return $this->redirectToRoute('app_book_index');
    }

    /**
     * Recherche par catégorie
     */
    #[Route('/category/{slug}', name: 'app_book_by_category', methods: ['GET'])]
    public function byCategory(string $slug, Request $request): Response
    {
        $category = $this->categoryRepository->findOneBy(['slug' => $slug]);

        if (!$category) {
            throw $this->createNotFoundException('Catégorie non trouvée');
        }

        $page = $request->query->getInt('page', 1);
        $books = $this->bookRepository->findByCategory($category->getId(), $page);

        return $this->render('book/by_category.html.twig', [
            'category' => $category,
            'books' => $books,
            'current_page' => $page,
        ]);
    }

    /**
     * Livres populaires
     */
    #[Route('/popular', name: 'app_book_popular', methods: ['GET'])]
    public function popular(): Response
    {
        $books = $this->bookRepository->findMostPopular(20);

        return $this->render('book/popular.html.twig', [
            'books' => $books,
        ]);
    }

    /**
     * Nouveautés
     */
    #[Route('/recent', name: 'app_book_recent', methods: ['GET'])]
    public function recent(): Response
    {
        $books = $this->bookRepository->findRecent(20);

        return $this->render('book/recent.html.twig', [
            'books' => $books,
        ]);
    }

    /**
     * Autocomplétion pour la recherche (retourne du JSON)
     *
     * Design Pattern : API Endpoint
     * Utilisé pour l'autocomplete avec Symfony UX
     */
    #[Route('/api/autocomplete', name: 'app_book_autocomplete', methods: ['GET'])]
    public function autocomplete(Request $request): Response
    {
        $query = $request->query->get('query', '');

        if (strlen($query) < 2) {
            return $this->json([]);
        }

        $results = $this->bookRepository->findForAutocomplete($query);

        return $this->json($results);
    }
}
