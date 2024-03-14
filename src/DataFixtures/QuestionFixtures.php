<?php

namespace App\DataFixtures;

use App\Entity\User;
use App\Entity\Question;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;

class QuestionFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // Crée plusieurs utilisateurs
        for ($i = 1; $i <= 5; $i++) {
            $user = new User();
            $user->setFirstname('Prénom' . $i);
            $user->setLastname('Nom' . $i);
            $user->setEmail('Prénom' . $i . 'Nom' . $i . '@gmail.com');
            $user->setPassword('azerty');
            $user->setPicture('https://randomuser.me/api/portraits/men/' . $i . '.jpg'); // Incrémente le numéro pour la photo de profil
            $user->setRoles(['ROLE_USER']);
            $manager->persist($user);

            // Crée plusieurs questions pour chaque utilisateur
            for ($j = 1; $j <= 3; $j++) {
                $question = new Question();
                $question->setTitle('Question de l\'utilisateur ' . $i);
                $question->setContent('Contenu de la question ' . $j);
                $question->setCreatedAt(new \DateTimeImmutable());
                $question->setRating(0);
                $question->setNbrOfResponse(0);
                $question->setAuthor($user);
                $manager->persist($question);
            }
        }

        // Flush pour sauvegarder en base de données
        $manager->flush();
    }
}
