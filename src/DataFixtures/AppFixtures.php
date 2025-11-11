<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Author;
use App\Entity\Book;
use App\Entity\Category;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Data Fixtures - Génération de données de test
 *
 * Design Pattern : Fixture (Test Data Builder)
 * Génère des données cohérentes pour le développement et les tests
 *
 * Utilisation :
 * php bin/console doctrine:fixtures:load
 */
class AppFixtures extends Fixture
{
    public function __construct(
        private UserPasswordHasherInterface $passwordHasher
    ) {}

    public function load(ObjectManager $manager): void
    {
        // Création des catégories
        $categories = $this->createCategories($manager);

        // Création des auteurs
        $authors = $this->createAuthors($manager);

        // Création des utilisateurs
        $this->createUsers($manager);

        // Création des livres
        $this->createBooks($manager, $categories, $authors);

        $manager->flush();
    }

    private function createCategories(ObjectManager $manager): array
    {
        $categoriesData = [
            ['Roman', 'Œuvres de fiction narrative'],
            ['Science-Fiction', 'Littérature d\'anticipation'],
            ['Policier', 'Romans policiers et thrillers'],
            ['Fantasy', 'Littérature fantastique'],
            ['Histoire', 'Livres historiques'],
            ['Biographie', 'Récits de vie'],
            ['Philosophie', 'Ouvrages philosophiques'],
            ['Science', 'Vulgarisation scientifique'],
        ];

        $categories = [];
        foreach ($categoriesData as [$name, $description]) {
            $category = new Category();
            $category->setName($name);
            $category->setDescription($description);
            $category->setSlug(strtolower(str_replace([' ', 'é', 'è'], ['_', 'e', 'e'], $name)));

            $manager->persist($category);
            $categories[] = $category;
        }

        return $categories;
    }

    private function createAuthors(ObjectManager $manager): array
    {
        $authorsData = [
            ['Victor', 'Hugo', 1802],
            ['Albert', 'Camus', 1913],
            ['J.K.', 'Rowling', 1965],
            ['Isaac', 'Asimov', 1920],
            ['Agatha', 'Christie', 1890],
            ['George', 'Orwell', 1903],
            ['Stephen', 'King', 1947],
            ['J.R.R.', 'Tolkien', 1892],
        ];

        $authors = [];
        foreach ($authorsData as [$firstName, $lastName, $birthYear]) {
            $author = new Author();
            $author->setFirstName($firstName);
            $author->setLastName($lastName);
            $author->setBirthYear($birthYear);

            $manager->persist($author);
            $authors[] = $author;
        }

        return $authors;
    }

    private function createUsers(ObjectManager $manager): void
    {
        // Admin
        $admin = new User();
        $admin->setEmail('admin@bibliotheque.fr');
        $admin->setFirstName('Admin');
        $admin->setLastName('Bibliothèque');
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setPassword($this->passwordHasher->hashPassword($admin, 'admin123'));
        $manager->persist($admin);

        // Utilisateurs de test
        for ($i = 1; $i <= 5; $i++) {
            $user = new User();
            $user->setEmail("user{$i}@example.com");
            $user->setFirstName("User{$i}");
            $user->setLastName("Test");
            $user->setPassword($this->passwordHasher->hashPassword($user, 'password'));
            $manager->persist($user);
        }
    }

    private function createBooks(ObjectManager $manager, array $categories, array $authors): void
    {
        $booksData = [
            ['Les Misérables', '9782070409228', 1862, 'Un des plus grands romans français', 0, 0],
            ['L\'Étranger', '9782070360024', 1942, 'Roman existentialiste', 0, 1],
            ['Harry Potter à l\'école des sorciers', '9782070584628', 1997, 'Le début d\'une saga mythique', 3, 2],
            ['Fondation', '9782070360260', 1951, 'Saga de science-fiction épique', 1, 3],
            ['Le Crime de l\'Orient-Express', '9782253004516', 1934, 'Enquête d\'Hercule Poirot', 2, 4],
            ['1984', '9782070368228', 1949, 'Dystopie totalitaire', 1, 5],
            ['Ça', '9782253151340', 1986, 'Terreur à Derry', 3, 6],
            ['Le Seigneur des Anneaux', '9782266154345', 1954, 'Épopée fantasy', 3, 7],
        ];

        foreach ($booksData as $i => [$title, $isbn, $year, $description, $categoryIndex, $authorIndex]) {
            $book = new Book();
            $book->setTitle($title);
            $book->setIsbn($isbn);
            $book->setPublishedYear($year);
            $book->setDescription($description);
            $book->setCategory($categories[$categoryIndex]);
            $book->setAuthor($authors[$authorIndex]);
            $book->setTotalCopies(rand(3, 10));
            $book->setAvailableCopies($book->getTotalCopies());

            $manager->persist($book);
        }
    }
}
