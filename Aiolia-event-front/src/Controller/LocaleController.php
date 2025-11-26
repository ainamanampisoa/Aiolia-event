<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class LocaleController extends AbstractController
{
    #[Route('/locale/{locale}', name: 'set_locale', methods: ['GET'])]
    public function setLocale(string $locale, Request $request): RedirectResponse
    {
        $allowedLocales = ['fr', 'en'];

        if (!\in_array($locale, $allowedLocales, true)) {
            $locale = 'fr';
        }

        $session = $request->getSession();
        if (!$session->isStarted()) {
            $session->start();
        }

        // Stocker la locale dans la session pour les prochains appels
        $session->set('_locale', $locale);

        // Essayer de revenir sur la page précédente, sinon rediriger vers l'accueil
        $referer = $request->headers->get('referer');

        if ($referer) {
            return $this->redirect($referer);
        }

        return $this->redirectToRoute('home');
    }
}


