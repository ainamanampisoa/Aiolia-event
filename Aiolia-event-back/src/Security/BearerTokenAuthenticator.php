<?php

namespace App\Security;

use App\Service\TokenService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class BearerTokenAuthenticator extends AbstractAuthenticator
{
    public function __construct(
        private readonly TokenService $tokenService
    ) {
    }

    public function supports(Request $request): ?bool
    {
        $authorization = $request->headers->get('Authorization');

        return is_string($authorization) && str_starts_with($authorization, 'Bearer ');
    }

    public function authenticate(Request $request): Passport
    {
        $authorization = $request->headers->get('Authorization');

        if (!is_string($authorization) || !str_starts_with($authorization, 'Bearer ')) {
            throw new CustomUserMessageAuthenticationException('En-tête Authorization manquant.');
        }

        $accessToken = trim(substr($authorization, 7));

        if ($accessToken === '') {
            throw new CustomUserMessageAuthenticationException('Token d’accès vide.');
        }

        try {
            $payload = $this->tokenService->decodeAccessToken($accessToken);
        } catch (AuthenticationException $exception) {
            throw new CustomUserMessageAuthenticationException($exception->getMessage(), [], 0, $exception);
        }

        $userId = (string) ($payload['sub'] ?? '');

        if ($userId === '') {
            throw new CustomUserMessageAuthenticationException('Token d’accès invalide.');
        }

        return new SelfValidatingPassport(new UserBadge($userId, fn (string $identifier) => $this->tokenService->getUserFromAccessToken($accessToken)));
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return new JsonResponse([
            'success' => false,
            'error' => [
                'message' => $exception->getMessage(),
            ],
        ], Response::HTTP_UNAUTHORIZED);
    }

    public function supportsRememberMe(): bool
    {
        return false;
    }
}


