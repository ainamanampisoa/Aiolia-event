<?php

namespace App\Controller\Organisateur;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/organisateur/tickets')]
#[IsGranted('ROLE_ORGANIZER')]
class TicketsController extends AbstractController
{
    /**
     * Page principale de gestion des billets
     */
    #[Route('', name: 'organisateur_tickets_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('Organisateur/ticket/index.html.twig');
    }
}

