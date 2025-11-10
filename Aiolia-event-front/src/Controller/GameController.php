<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class GameController extends AbstractController
{
    #[Route('/ticket-chance', name: 'ticket_chance')]
    public function ticketChance(): Response
    {
        return $this->render('game/ticket_chance.html.twig');
    }
}
