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
        // Rediriger vers la page existante des promotions
        return $this->redirectToRoute('app_promotion_index');
    }

    #[Route('/new', name: 'app_promotion_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        return $this->render('promotion/new.html.twig');
    }

    #[Route('/{id}/edit', name: 'app_promotion_edit', methods: ['GET', 'POST'])]
    public function edit(int $id, Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        return $this->render('promotion/edit.html.twig');
    }

    #[Route('/history', name: 'app_promotion_history')]
    public function history(): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        return $this->render('promotion/history.html.twig');
    }
}

