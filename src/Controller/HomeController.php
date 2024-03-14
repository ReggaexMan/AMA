<?php

namespace App\Controller;

use App\Repository\QuestionRepository;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;


class HomeController extends AbstractController
{
    #[Route('/', name: 'home')]
    public function index(QuestionRepository $questionRepository): Response
    {
        $questions = $questionRepository->getLastQuestionsWithAuthors();
        return $this->render('home/index.html.twig', [
            'questions' => $questions
        ]);
    }


    public function cookie(Request $request): Response
    {
        $response = new Response();

        // récupération du consentement depuis un cookie
        $cookieConsent = $request->cookies->get('cookieConsent');

        if ($cookieConsent === "true") {
            $cookie = Cookie::create('nom_du_cookie')
                ->withValue('valeur_du_cookie')
                ->withExpires(new \DateTime('+1 year'))
                ->withSecure(true)
                ->withHttpOnly(true);
            $response->headers->setCookie($cookie);
        }

        return $response;
    }
}
