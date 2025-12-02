<?php

namespace App\Controller\Organisateur;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/organisateur/promotions')]
#[IsGranted('ROLE_ORGANIZER')]
class PromotionController extends AbstractController
{
    #[Route('', name: 'organisateur_promotions_index')]
    public function index(): Response
    {
        return $this->render('Organisateur/promotion/index.html.twig');
    }

    #[Route('/new', name: 'organisateur_promotions_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        return $this->render('Organisateur/promotion/new.html.twig');
    }
}

