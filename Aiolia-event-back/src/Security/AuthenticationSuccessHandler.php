<?php

namespace App\Security;

use App\Entity\User;
use App\Enum\Role;
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

        if ($user instanceof User) {
            $role = $user->getRole();

            return match ($role) {
                Role::ADMIN => new RedirectResponse($this->urlGenerator->generate('app_reports_statistiques')),
                Role::ORGANIZER => new RedirectResponse($this->urlGenerator->generate('organisateur_dashboard_statistiques')),
                default => new RedirectResponse($this->urlGenerator->generate('app_event_index')),
            };
        }

        return new RedirectResponse($this->urlGenerator->generate('app_login'));
    }

}

