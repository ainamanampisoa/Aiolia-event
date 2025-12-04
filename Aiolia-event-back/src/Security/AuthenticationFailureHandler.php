<?php

namespace App\Security;

use App\Entity\OrganizerProfile;
use App\Entity\User;
use App\Enum\Role as UserRoleEnum;
use App\Repository\Organisateur\OrganizerProfileRepository;
use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;

class AuthenticationFailureHandler implements AuthenticationFailureHandlerInterface
{
    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
        private UserRepository $userRepository,
        private OrganizerProfileRepository $organizerProfileRepository,
        private RequestStack $requestStack,
    ) {
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        $identifier = $request->request->get('identifier', '');
        $errorMessage = null;

        $previousException = $exception->getPrevious();
        if ($previousException instanceof CustomUserMessageAuthenticationException) {
            $errorMessage = $previousException->getMessage();
        } elseif ($exception instanceof CustomUserMessageAuthenticationException) {
            $errorMessage = $exception->getMessage();
        }

        if ($errorMessage === null) {
            $errorMessage = $this->checkOrganizerValidation($identifier);

            if ($errorMessage === null) {
                $errorMessage = 'Identifiant ou mot de passe incorrect.';
            }
        }

        $this->addFlashMessage('error', $errorMessage);

        return new \Symfony\Component\HttpFoundation\RedirectResponse(
            $this->urlGenerator->generate('app_login')
        );
    }


    private function checkOrganizerValidation(string $identifier): ?string
    {
        if (empty($identifier)) {
            return null;
        }

        try {
            $user = $this->userRepository->loadUserByIdentifier($identifier);

            if (!($user instanceof User) || $user->getRole() !== UserRoleEnum::ORGANIZER) {
                return null;
            }

            $organizerProfile = $this->organizerProfileRepository->findOneBy(['utilisateur' => $user]);

            if (!$organizerProfile instanceof OrganizerProfile) {
                return null;
            }

            $statutVerification = $organizerProfile->getStatutVerification();

            return match ($statutVerification) {
                OrganizerProfile::STATUS_PENDING => 'Votre compte organisateur est en attente de validation.',
                OrganizerProfile::STATUS_REJECTED => 'Votre compte organisateur a été refusé. Contactez l\'administration.',
                default => null,
            };
        } catch (\Exception $e) {
            return null;
        }
    }

    private function addFlashMessage(string $type, string $message): void
    {
        $session = $this->requestStack->getSession();
        if ($session) {
            /** @var FlashBagInterface $flashBag */
            $flashBag = $session->getBag('flashes');
            $flashBag->add($type, $message);
        }
    }
}

