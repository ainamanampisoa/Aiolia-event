<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ProfileController extends AbstractController
{
    #[Route('/profile', name: 'profile_index')]
    public function index(): Response
    {
        return $this->render('profile/index.html.twig');
    }

    #[Route('/profile/history', name: 'profile_history')]
    public function history(): Response
    {
        return $this->render('profile/history.html.twig');
    }

    #[Route('/profile/wallet', name: 'profile_wallet')]
    public function wallet(): Response
    {
        return $this->render('profile/wallet.html.twig');
    }

    #[Route('/profile/favorites', name: 'profile_favorites')]
    public function favorites(): Response
    {
        return $this->render('profile/favorites.html.twig');
    }

    #[Route('/profile/calendar', name: 'profile_calendar')]
    public function calendar(): Response
    {
        return $this->render('profile/calendar.html.twig');
    }

    #[Route('/profile/stats', name: 'profile_stats')]
    public function stats(): Response
    {
        return $this->render('profile/stats.html.twig');
    }

    #[Route('/profile/settings', name: 'profile_settings')]
    public function settings(): Response
    {
        return $this->render('profile/settings.html.twig');
    }
}
