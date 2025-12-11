<?php

namespace App\Controller\Organisateur;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/organisateur/waitlist')]
#[IsGranted('ROLE_ORGANIZER')]
class WaitlistController extends AbstractController
{
    
    #[Route('', name: 'organisateur_waitlist_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('Organisateur/waitlist/index.html.twig', []);
    }
}

