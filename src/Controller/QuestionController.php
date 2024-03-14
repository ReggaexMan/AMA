<?php

namespace App\Controller;

use App\Entity\Vote;
use App\Entity\Comment;
use App\Entity\Question;
use App\Form\CommentType;
use App\Form\QuestionType;
use App\Repository\VoteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class QuestionController extends AbstractController
{
    #[Route('/question/ask', name: 'question_form')]
    #[IsGranted('ROLE_USER')]
    public function index(Request $request, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        $question = new Question();
        $formQuestion = $this->createForm(QuestionType::class, $question);

        $formQuestion->handleRequest($request);

        if ($formQuestion->isSubmitted() && $formQuestion->isValid()) {
            $question->setNbrOfResponse(0);
            $question->setRating(0);
            $question->setAuthor($user);
            $question->setCreatedAt(new \DateTimeImmutable());
            $em->persist($question);
            $em->flush();
            $this->addFlash('success', 'Votre question a été ajoutée');
            return $this->redirectToRoute('home');
        }

        return $this->render('question/index.html.twig', [
            'form' => $formQuestion->createView(),
        ]);
    }

    #[Route('/question/{id}', name: 'question_show')]
    public function show(Request $request, Question $question, EntityManagerInterface $em): Response
    {
        $options = [
            'question' => $question
        ];
        $user = $this->getUser();
        if ($user) {
            $comment = new Comment();
            $commentForm = $this->createForm(CommentType::class, $comment);
            $commentForm->handleRequest($request);
            if ($commentForm->isSubmitted() && $commentForm->isValid()) {
                $comment->setCreatedAt(new \DateTimeImmutable());
                $comment->setRating(0);
                $comment->setQuestion($question);
                $comment->setAuthor($user);
                $question->setNbrOfResponse($question->getNbrOfResponse() + 1);
                $em->persist($comment);
                $em->flush();
                $this->addFlash('success', 'Votre réponse a bien été ajoutée');
                return $this->redirect($request->getUri());
            }
            $options['form'] = $commentForm->createView();
        }
        return $this->render('question/show.html.twig', $options);
    }

    #[Route('/question/{id}/delete', name: 'question_delete', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function deleteQuestion(Request $request, Question $question, EntityManagerInterface $entityManager): Response
    {
        if ($this->isGranted('ROLE_ADMIN') || $this->getUser() === $question->getAuthor()) {

            if ($this->isCsrfTokenValid('delete' . $question->getId(), $request->request->get('_token'))) {
                $entityManager->remove($question);
                $entityManager->flush();
                $this->addFlash('success', 'La question a été supprimée.');
            }
        } else {
            $this->addFlash('error', 'Vous n\'êtes pas autorisé à supprimer cette question.');
        }

        return $this->redirectToRoute('home', []);
    }

    #[Route('/question/rating/{id}/{score}', name: 'question_rating')]
    #[IsGranted('ROLE_USER')]
    public function ratingQuestion(Request $request, Question $question, int $score, EntityManagerInterface $em, VoteRepository $voteRepo)
    {
        $user = $this->getUser();
        if ($user !== $question->getAuthor()) {
            $vote = $voteRepo->findOneBy([
                'author' => $user,
                'question' => $question
            ]);
            if ($vote) {
                if (($vote->getIsLiked() && $score > 0) || (!$vote->getIsLiked() && $score < 0)) {
                    $em->remove($vote);
                    $question->setRating($question->getRating() + ($score > 0 ? -1 : 1));
                } else {
                    $vote->setIsLiked(!$vote->getIsLiked());
                    $question->setRating($question->getRating() + ($score > 0 ? 2 : -2));
                }
            } else {
                $vote = new Vote();
                $vote->setAuthor($user);
                $vote->setQuestion($question);
                $vote->setIsLiked($score > 0 ? true : false);
                $question->setRating($question->getRating() + $score);
                $em->persist($vote);
            }
            $em->flush();
        }
        $referer = $request->server->get('HTTP_REFERER');
        return $referer ? $this->redirect($referer) : $this->redirectToRoute('home');
    }

    #[Route('/comment/rating/{id}/{score}', name: 'comment_rating')]
    #[IsGranted('ROLE_USER')]
    public function ratingComment(Request $request, Comment $comment, int $score, EntityManagerInterface $em, VoteRepository $voteRepo)
    {
        $user = $this->getUser();
        if ($user !== $comment->getAuthor()) {
            $vote = $voteRepo->findOneBy([
                'author' => $user,
                'comment' => $comment
            ]);
            if ($vote) {
                if (($vote->getIsLiked() && $score > 0) || (!$vote->getIsLiked() && $score < 0)) {
                    $em->remove($vote);
                    $comment->setRating($comment->getRating() + ($score > 0 ? -1 : 1));
                } else {
                    $vote->setIsLiked(!$vote->getIsLiked());
                    $comment->setRating($comment->getRating() + ($score > 0 ? 2 : -2));
                }
            } else {
                $vote = new Vote();
                $vote->setAuthor($user);
                $vote->setComment($comment);
                $vote->setIsLiked($score > 0 ? true : false);
                $comment->setRating($comment->getRating() + $score);
                $em->persist($vote);
            }
            $em->flush();
        }
        $referer = $request->server->get('HTTP_REFERER');
        return $referer ? $this->redirect($referer) : $this->redirectToRoute('home');
    }

    #[Route('/comment/{id}/delete', name: 'comment_delete', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function deleteComment(Request $request, Comment $comment, EntityManagerInterface $entityManager): Response
    {
        // Obtient l'identifiant de la question associée au commentaire
        $questionId = $comment->getQuestion()->getId();

        if ($this->isCsrfTokenValid('delete' . $comment->getId(), $request->request->get('_token'))) {
            $entityManager->remove($comment);
            $entityManager->flush();
            $this->addFlash('success', 'Votre commentaire a bien été supprimé !');
            return $this->redirectToRoute('question_show', ['id' => $questionId]);
        }

        // Redirige vers la question à laquelle le commentaire appartenait
        return $this->redirectToRoute('question_show', ['id' => $questionId]);
    }


    #[Route('/comment/{id}/edit-form', name: 'comment_edit_form', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function editForm(Request $request, Comment $comment, EntityManagerInterface $em, VoteRepository $voteRepo): Response
    {
        $form = $this->createForm(CommentType::class, $comment);
        $form->handleRequest($request);
        // Obtient l'identifiant de la question associée au commentaire
        $questionId = $comment->getQuestion()->getId();

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Commentaire mis à jour avec succès !');
            return $this->redirectToRoute('question_show', ['id' => $questionId]);
        }

        return $this->render('comment/edit_comment_form.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
