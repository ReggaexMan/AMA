<?php

namespace App\Security;

use App\Entity\User;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class UserChecker implements UserCheckerInterface
{
    public function checkPreAuth(UserInterface $user): void
    {
        if (!$user instanceof User) {
            return;
        }

        if (!$user->isIsActive()) {
            // L'utilisateur n'est pas actif, refuse la connexion avec un message personnalisé
            throw new CustomUserMessageAccountStatusException('Votre compte n\'est pas actif.');
        }
    }

    public function checkPostAuth(UserInterface $user): void
    {
        // Là tu peux ajouter des vérifs en plus après lauthentification
    }
}
