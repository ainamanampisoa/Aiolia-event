<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/tickets')]
class TicketController extends AbstractController
{
    #[Route('', name: 'app_ticket_index')]
    public function index(): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        // TODO: Récupérer les billets depuis la base de données
        
        return $this->render('ticket/index.html.twig');
    }

    #[Route('/categories', name: 'app_ticket_categories')]
    public function categories(): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        return $this->render('ticket/categories.html.twig');
    }

    #[Route('/qrcodes', name: 'app_ticket_qrcodes')]
    public function qrcodes(): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        return $this->render('ticket/qrcodes.html.twig');
    }

    #[Route('/scanning', name: 'app_ticket_scanning')]
    public function scanning(): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        return $this->render('ticket/scanning.html.twig');
    }

    #[Route('/stock-alerts', name: 'app_ticket_stock_alerts')]
    public function stockAlerts(): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        return $this->render('ticket/stock_alerts.html.twig');
    }
}

