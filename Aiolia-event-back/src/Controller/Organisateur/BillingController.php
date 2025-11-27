<?php

namespace App\Controller\Organisateur;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/organisateur/billing')]
#[IsGranted('ROLE_ORGANIZER')]
class BillingController extends AbstractController
{
    /**
     * Page principale de facturation
     */
    #[Route('', name: 'organisateur_billing_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('Organisateur/billing/index.html.twig', []);
    }
}

