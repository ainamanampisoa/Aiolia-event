<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/notifications')]
class NotificationController extends AbstractController
{
    #[Route('', name: 'app_notification_index')]
    public function index(): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        return $this->render('notification/index.html.twig');
    }

    #[Route('/alerts', name: 'app_notification_alerts')]
    public function alerts(): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        return $this->render('notification/alerts.html.twig');
    }

    #[Route('/reminders', name: 'app_notification_reminders')]
    public function reminders(): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        return $this->render('notification/reminders.html.twig');
    }

    #[Route('/settings', name: 'app_notification_settings')]
    public function settings(): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        return $this->render('notification/settings.html.twig');
    }
}

