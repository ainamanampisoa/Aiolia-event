<?php

namespace App\Controller\Organisateur;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/organisateur/reports')]
#[IsGranted('ROLE_ORGANIZER')]
class ReportsController extends AbstractController
{
    /**
     * Page principale des rapports
     */
    #[Route('', name: 'organisateur_reports_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('Organisateur/reports/index.html.twig', []);
    }
}

