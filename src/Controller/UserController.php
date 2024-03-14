<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use App\Form\ContactType;
use Symfony\Component\Mime\Email;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class UserController extends AbstractController
{
    #[Route('/user/{id}', name: 'user')]
    #[IsGranted('ROLE_USER')]
    public function userProfile(User $user): Response
    {
        $currentUser = $this->getUser();
        if ($currentUser === $user) {
            return $this->redirectToRoute('current_user');
        }
        return $this->render('user/show.html.twig', [
            'user' => $user
        ]);
    }

    #[Route('/user', name: 'current_user')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function currentUserProfile(Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $passwordHasher, MailerInterface $mailer): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $userForm = $this->createForm(UserType::class, $user);
        $userForm->remove('password');
        $userForm->add('newPassword', PasswordType::class, ['label' => 'Nouveau mot de passe', 'required' => false]);
        $userForm->handleRequest($request);
        if ($userForm->isSubmitted() && $userForm->isValid()) {
            $newPassword = $user->getNewPassword();
            if ($newPassword) {
                $hash = $passwordHasher->hashPassword($user, $newPassword);
                $user->setPassword($hash);
            }
            $em->flush();
            $this->addFlash('success', 'Modifications sauvegardées !');
        }

        $contactForm = $this->createForm(ContactType::class);
        $contactForm->handleRequest($request);

        if ($contactForm->isSubmitted() && $contactForm->isValid()) {
            $contactData = $contactForm->getData();

            $email = (new TemplatedEmail())
                ->from($contactData['email'])
                ->to('steve@ama.fr')
                ->subject('Nouveau Message de Contact: ' . $contactData['subject'])
                ->htmlTemplate('@email_templates/contact_email.html.twig')
                ->context([
                    'name' => $contactData['name'],
                    'senderEmail' => $contactData['email'],
                    'subject' => $contactData['subject'],
                    'message' => $contactData['message'],
                ]);

            $mailer->send($email);

            $this->addFlash('success', 'Votre message a été envoyé.');
            return $this->redirectToRoute('current_user');
        }

        return $this->render('user/index.html.twig', [
            'form' => $userForm->createView(),
            'contactForm' => $contactForm->createView(),
        ]);
    }

    #[Route('/deactivate-account', name: 'deactivate_account')]
    public function deactivateAccount(EntityManagerInterface $entityManager, TokenStorageInterface $tokenStorage, SessionInterface $session): Response
    {
        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException();
        }

        $user->setIsActive(false);
        $entityManager->persist($user);
        $entityManager->flush();

        $this->addFlash('success', 'Votre compte a bien été désactivé.');

        // Déconnecter l'utilisateur
        $tokenStorage->setToken(null);
        $session->invalidate(); // Utilise directement l'objet $session injecté

        return $this->redirectToRoute('home'); // Rediriger l'utilisateur vers la page d'accueil
    }
}
