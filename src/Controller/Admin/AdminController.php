<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Entity\Comment;
use App\Entity\Question;
use App\Repository\UserRepository;
use App\Repository\CommentRepository;
use App\Repository\QuestionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class AdminController extends AbstractController
{
    #[Route('/admin', name: 'app_admin')]
    #[IsGranted("ROLE_ADMIN")]
    public function index(): Response
    {
        return $this->render('admin/index.html.twig', [
            'controller_name' => 'AdminController',
        ]);
    }

    #[Route('/admin/users', name: 'admin_users')]
    #[IsGranted("ROLE_ADMIN")]
    public function manageUsers(UserRepository $userRepository): Response
    {
        $users = $userRepository->findAll();

        return $this->render('admin/users.html.twig', [
            'users' => $users,
        ]);
    }

    #[Route('/admin/questions', name: 'admin_questions')]
    #[IsGranted("ROLE_ADMIN")]
    public function manageQuestions(QuestionRepository $questionRepository): Response
    {
        $questions = $questionRepository->findAll();

        return $this->render('admin/questions.html.twig', [
            'questions' => $questions,
        ]);
    }

    #[Route('/admin/comments', name: 'admin_comments')]
    #[IsGranted("ROLE_ADMIN")]
    public function manageComments(CommentRepository $commentRepository): Response
    {
        $comments = $commentRepository->findAll();

        return $this->render('admin/comments.html.twig', [
            'comments' => $comments,
        ]);
    }

    #[Route('/admin/users/toggle/{id}', name: 'admin_toggle_user')]
    #[IsGranted("ROLE_ADMIN")]
    public function toggleUser(User $user, EntityManagerInterface $entityManager): Response
    {
        $user->setIsActive(!$user->isIsActive());
        $entityManager->persist($user);
        $entityManager->flush();

        $this->addFlash(
            'success',
            'Le compte de ' . $user->getFullname() . ' a été ' . ($user->isIsActive() ? 'réactivé' : 'désactivé') . '.'
        );

        return $this->redirectToRoute('admin_users');
    }


    #[Route('/admin/questions/delete/{id}', name: 'admin_question_delete', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function deleteQuestion(Request $request, Question $question, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $question->getId(), $request->request->get('_token'))) {
            $entityManager->remove($question);
            $entityManager->flush();
            $this->addFlash('success', 'La question a été supprimée.');
        } else {
            $this->addFlash('error', 'Erreur lors de la suppression de la question.');
        }

        return $this->redirectToRoute('admin_questions');
    }

    #[Route('/admin/comments/delete/{id}', name: 'admin_comment_delete', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function deleteComment(Request $request, Comment $comment, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $comment->getId(), $request->request->get('_token'))) {
            $entityManager->remove($comment);
            $entityManager->flush();
            $this->addFlash('success', 'La question a été supprimée.');
        } else {
            $this->addFlash('error', 'Erreur lors de la suppression de la question.');
        }

        return $this->redirectToRoute('admin_questions');
    }
}
