<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;

class AuthenticationSuccessHandler implements AuthenticationSuccessHandlerInterface
{
    public function __construct(
        private UrlGeneratorInterface $urlGenerator
    ) {
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token): RedirectResponse
    {
        $user = $token->getUser();
        
        // Rediriger selon le rôle de l'utilisateur
        if ($user && method_exists($user, 'getRole')) {
            $role = $user->getRole();
            
            if ($role === 'admin') {
                return new RedirectResponse($this->urlGenerator->generate('app_reports_statistiques'));
            } elseif ($role === 'organizer') {
                return new RedirectResponse($this->urlGenerator->generate('organisateur_dashboard_statistiques'));
            }
        }
        
        // Par défaut, rediriger vers la page statistiques admin
        return new RedirectResponse($this->urlGenerator->generate('app_reports_statistiques'));
    }
}

