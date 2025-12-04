<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\Security\AuthService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AuthController extends AbstractController
{
    public function __construct(
        private readonly AuthService $authService
    ) {
    }

    #[Route('/login', name: 'login', methods: ['GET', 'POST'])]
    public function loginPage(Request $request): Response
    {
        $session = $request->getSession();
        $error = null;
        $lastEmail = '';

        if ($session->has('user')) {
            $sessionUser = $session->get('user');
            if (is_array($sessionUser)) {
                $lastEmail = $sessionUser['email'] ?? ($sessionUser['profile']['email'] ?? '');
            }
        }

        if ($request->isMethod('POST')) {
            $email = trim((string) $request->request->get('email'));
            $password = (string) $request->request->get('password');
            $lastEmail = $email;

            try {
                $result = $this->authService->login(
                    $email,
                    $password,
                    $request->headers->get('User-Agent'),
                    $request->getClientIp()
                );

                $session->set('user', [
                    'id' => $result['user']['id'],
                    'email' => $result['user']['email'],
                    'username' => $result['user']['full_name'],
                    'profile' => $result['user'],
                    'tokens' => $result['tokens'],
                ]);

                return $this->redirectToRoute('home');
            } catch (\Throwable $exception) {
                $error = $exception->getMessage();
            }
        }

        return $this->render('auth/login.html.twig', [
            'last_email' => $lastEmail,
            'error' => $error,
        ]);
    }

    #[Route('/register', name: 'register')]
    public function registerPage(): Response
    {
        return $this->render('auth/register.html.twig');
    }

    #[Route('/forgot-password', name: 'forgot_password')]
    public function forgotPasswordPage(): Response
    {
        return $this->render('auth/forgot_password.html.twig');
    }

    #[Route('/logout', name: 'logout')]
    public function logoutPage(Request $request): Response
    {
        $session = $request->getSession();
        $sessionUser = $session->get('user');

        if (is_array($sessionUser)) {
            $refreshToken = $sessionUser['tokens']['refresh_token'] ?? null;
            $email = $sessionUser['email'] ?? ($sessionUser['profile']['email'] ?? null);

            if (null !== $email) {
                $this->authService->logoutByEmail($email, $refreshToken);
            }
        }

        $session->remove('user');
        $session->invalidate();

        return $this->redirectToRoute('home');
    }

    #[Route('/profile', name: 'profile')]
    public function profilePage(): Response
    {
        return $this->render('profile/index.html.twig');
    }

    #[Route('/api/auth/register', name: 'api_auth_register', methods: ['POST'])]
    public function register(Request $request): JsonResponse
    {
        $data = $this->extractRequestData($request);

        try {
            $result = $this->authService->register(
                $data,
                $request->headers->get('User-Agent'),
                $request->getClientIp()
            );

            return $this->json([
                'status' => 'success',
                'message' => 'Inscription réussie.',
                'data' => $result,
            ], Response::HTTP_CREATED);
        } catch (\InvalidArgumentException $exception) {
            return $this->json([
                'status' => 'error',
                'message' => $exception->getMessage(),
            ], Response::HTTP_BAD_REQUEST);
        } catch (\Throwable $exception) {
            return $this->json([
                'status' => 'error',
                'message' => 'Une erreur est survenue lors de l\'inscription.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/api/auth/login', name: 'api_auth_login', methods: ['POST'])]
    public function login(Request $request): JsonResponse
    {
        $data = $this->extractRequestData($request);

        try {
            if (!empty($data['provider'])) {
                $result = $this->authService->loginWithProvider(
                    $data,
                    $request->headers->get('User-Agent'),
                    $request->getClientIp()
                );
            } else {
                $email = (string) ($data['email'] ?? '');
                $password = (string) ($data['password'] ?? '');

                $result = $this->authService->login(
                    $email,
                    $password,
                    $request->headers->get('User-Agent'),
                    $request->getClientIp()
                );
            }

            return $this->json([
                'status' => 'success',
                'message' => 'Connexion réussie.',
                'data' => $result,
            ]);
        } catch (\InvalidArgumentException $exception) {
            return $this->json([
                'status' => 'error',
                'message' => $exception->getMessage(),
            ], Response::HTTP_BAD_REQUEST);
        } catch (\Throwable $exception) {
            return $this->json([
                'status' => 'error',
                'message' => 'Une erreur est survenue lors de la connexion.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/api/auth/logout', name: 'api_auth_logout', methods: ['POST'])]
    public function logout(Request $request): JsonResponse
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            return $this->json([
                'status' => 'error',
                'message' => 'Utilisateur non authentifié.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $data = $this->extractRequestData($request);
        $refreshToken = $data['refresh_token'] ?? $request->headers->get('X-Refresh-Token');

        $this->authService->logout($user, is_string($refreshToken) ? $refreshToken : null);

        return $this->json([
            'status' => 'success',
            'message' => 'Déconnexion effectuée.',
        ]);
    }

    #[Route('/api/auth/refresh', name: 'api_auth_refresh', methods: ['POST'])]
    public function refreshToken(Request $request): JsonResponse
    {
        $data = $this->extractRequestData($request);
        $refreshToken = $data['refresh_token'] ?? $request->headers->get('X-Refresh-Token');

        if (!is_string($refreshToken) || '' === trim($refreshToken)) {
            return $this->json([
                'status' => 'error',
                'message' => 'Le refresh token est requis.',
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            $tokens = $this->authService->refreshTokens(
                $refreshToken,
                $request->headers->get('User-Agent'),
                $request->getClientIp()
            );

            return $this->json([
                'status' => 'success',
                'message' => 'Token régénéré.',
                'data' => $tokens,
            ]);
        } catch (\InvalidArgumentException $exception) {
            return $this->json([
                'status' => 'error',
                'message' => $exception->getMessage(),
            ], Response::HTTP_BAD_REQUEST);
        } catch (\Throwable $exception) {
            return $this->json([
                'status' => 'error',
                'message' => 'Une erreur est survenue lors du renouvellement.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/api/auth/profile', name: 'api_auth_profile', methods: ['GET'])]
    public function getProfile(): JsonResponse
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            return $this->json([
                'status' => 'error',
                'message' => 'Utilisateur non authentifié.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        return $this->json([
            'status' => 'success',
            'data' => [
                'user' => $this->authService->serializeUser($user),
            ],
        ]);
    }

    #[Route('/api/auth/profile', name: 'api_auth_update_profile', methods: ['PUT', 'PATCH'])]
    public function updateProfile(Request $request): JsonResponse
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            return $this->json([
                'status' => 'error',
                'message' => 'Utilisateur non authentifié.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $data = $this->extractRequestData($request);

        if (empty($data)) {
            return $this->json([
                'status' => 'error',
                'message' => 'Aucune donnée à mettre à jour.',
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            $updated = $this->authService->updateProfile($user, $data);

            return $this->json([
                'status' => 'success',
                'message' => 'Profil mis à jour.',
                'data' => [
                    'user' => $updated,
                ],
            ]);
        } catch (\InvalidArgumentException $exception) {
            return $this->json([
                'status' => 'error',
                'message' => $exception->getMessage(),
            ], Response::HTTP_BAD_REQUEST);
        } catch (\Throwable $exception) {
            return $this->json([
                'status' => 'error',
                'message' => 'Une erreur est survenue lors de la mise à jour.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function extractRequestData(Request $request): array
    {
        $data = [];

        if (0 !== $request->getContentLength()) {
            $contentType = (string) $request->headers->get('Content-Type', '');
            if (str_contains($contentType, 'application/json')) {
                $content = (string) $request->getContent();
                if ('' !== $content) {
                    try {
                        $decoded = json_decode($content, true, flags: JSON_THROW_ON_ERROR);
                        if (is_array($decoded)) {
                            $data = $decoded;
                        }
                    } catch (\JsonException $exception) {
                        throw new \InvalidArgumentException('Le format JSON est invalide.', 0, $exception);
                    }
                }
            }
        }

        if (!empty($request->request->all())) {
            $data = array_merge($data, $request->request->all());
        }

        return $data;
    }
}