<?php

namespace App\Controller\Api;

use App\Repository\UserRepository;
use App\Service\TokenService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/auth', name: 'api_auth_')]
class AuthTokenController extends AbstractController
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly TokenService $tokenService,
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    #[Route('/login', name: 'login', methods: ['POST'])]
    public function login(Request $request): JsonResponse
    {
        $payload = $this->decodeJsonBody($request);
        $identifier = trim((string) ($payload['identifier'] ?? ''));
        $password = $payload['password'] ?? null;

        if ($identifier === '' || !is_string($password)) {
            return $this->json([
                'success' => false,
                'error' => [
                    'message' => 'Identifiants de connexion manquants.',
                ],
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            $user = $this->userRepository->loadUserByIdentifier($identifier);
        } catch (\Throwable $exception) {
            return $this->json([
                'success' => false,
                'error' => [
                    'message' => 'Identifiants de connexion invalides.',
                ],
            ], Response::HTTP_UNAUTHORIZED);
        }

        if (!$user->estActif()) {
            return $this->json([
                'success' => false,
                'error' => [
                    'message' => 'Le compte n’est pas actif. Merci de contacter le support.',
                ],
            ], Response::HTTP_FORBIDDEN);
        }

        if (!$this->passwordHasher->isPasswordValid($user, $password)) {
            return $this->json([
                'success' => false,
                'error' => [
                    'message' => 'Identifiants de connexion invalides.',
                ],
            ], Response::HTTP_UNAUTHORIZED);
        }

        $user->setLastLoginAt(new \DateTimeImmutable());
        $tokens = $this->tokenService->issueTokenPair(
            $user,
            $request->headers->get('User-Agent'),
            $request->getClientIp(),
            sessionId: $payload['session_id'] ?? null,
            metadata: [
                'action' => 'login',
            ]
        );

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $this->json([
            'success' => true,
            'data' => $this->formatTokenResponse($tokens),
        ]);
    }

    #[Route('/refresh', name: 'refresh', methods: ['POST'])]
    public function refresh(Request $request): JsonResponse
    {
        $payload = $this->decodeJsonBody($request);
        $refreshToken = $payload['refresh_token'] ?? null;

        if (!is_string($refreshToken) || trim($refreshToken) === '') {
            return $this->json([
                'success' => false,
                'error' => [
                    'message' => 'Refresh token manquant.',
                ],
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            $tokens = $this->tokenService->rotateRefreshToken(
                $refreshToken,
                $request->headers->get('User-Agent'),
                $request->getClientIp(),
                [
                    'action' => 'refresh',
                ]
            );
        } catch (CustomUserMessageAuthenticationException $exception) {
            return $this->json([
                'success' => false,
                'error' => [
                    'message' => $exception->getMessage(),
                ],
            ], Response::HTTP_UNAUTHORIZED);
        }

        return $this->json([
            'success' => true,
            'data' => $this->formatTokenResponse($tokens),
        ]);
    }

    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    #[Route('/logout', name: 'logout', methods: ['POST'])]
    public function logout(Request $request): JsonResponse
    {
        $payload = $this->decodeJsonBody($request);
        $refreshToken = $payload['refresh_token'] ?? null;
        $allSessions = filter_var($payload['all_sessions'] ?? false, FILTER_VALIDATE_BOOL);

        if (is_string($refreshToken) && trim($refreshToken) !== '') {
            try {
                $this->tokenService->revokeRefreshToken($refreshToken, $this->getUser());
            } catch (CustomUserMessageAuthenticationException $exception) {
                return $this->json([
                    'success' => false,
                    'error' => [
                        'message' => $exception->getMessage(),
                    ],
                ], Response::HTTP_BAD_REQUEST);
            }
        }

        if ($allSessions === true && $this->getUser()) {
            $sessionId = is_string($payload['session_id'] ?? null) ? $payload['session_id'] : null;
            $this->tokenService->revokeAllForUser($this->getUser(), $sessionId);
        }

        return $this->json([
            'success' => true,
            'data' => [
                'message' => 'Déconnexion effectuée.',
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeJsonBody(Request $request): array
    {
        $content = (string) $request->getContent();

        if ($content === '') {
            return [];
        }

        try {
            $data = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return [];
        }

        return is_array($data) ? $data : [];
    }

    /**
     * @param array{
     *     access_token: string,
     *     access_token_expires_at: \DateTimeImmutable,
     *     refresh_token: string,
     *     refresh_token_expires_at: \DateTimeImmutable,
     *     session_id: string
     * } $tokens
     *
     * @return array<string, mixed>
     */
    private function formatTokenResponse(array $tokens): array
    {
        return [
            'token_type' => 'Bearer',
            'access_token' => $tokens['access_token'],
            'access_token_expires_at' => $tokens['access_token_expires_at']->format(\DateTimeInterface::ATOM),
            'refresh_token' => $tokens['refresh_token'],
            'refresh_token_expires_at' => $tokens['refresh_token_expires_at']->format(\DateTimeInterface::ATOM),
            'session_id' => $tokens['session_id'],
        ];
    }
}


