<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SocialController extends AbstractController
{
    #[Route('/friends', name: 'friends')]
    public function invitations(): Response
    {
        return $this->render('social/invitations.html.twig');
    }
}
