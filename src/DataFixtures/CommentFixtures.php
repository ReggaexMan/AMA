<?php

namespace App\DataFixtures;

use App\Entity\User;
use App\Entity\Comment;
use App\Entity\Question;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

class CommentFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        // Récupère tous les utilisateurs et questions créés précédemment
        $users = $manager->getRepository(User::class)->findAll();
        $questions = $manager->getRepository(Question::class)->findAll();

        // Crée des réponses pour chaque question avec des utilisateurs aléatoires
        foreach ($questions as $question) {
            $randomUser = $users[array_rand($users)]; // Sélectionne un utilisateur aléatoire
            $randomRating = rand(1, 5); // Génère une notation aléatoire entre 1 et 5

            $comment = new Comment();
            $comment->setContent('Réponse à la question : ' . $question->getTitle());
            $comment->setCreatedAt(new \DateTimeImmutable());
            $comment->setRating($randomRating);
            $comment->setQuestion($question);
            $comment->setAuthor($randomUser);

            $manager->persist($comment);
        }

        // Flush pour sauvegarder en base de données
        $manager->flush();
    }

    public function getDependencies() // Définit l'odre dans lequel les fixtures sont executées (d'abord les questions, puis ls réponses)
    {
        return [
            QuestionFixtures::class,
        ];
    }
}
