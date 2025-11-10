<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AuthController extends AbstractController
{
    #[Route('/login', name: 'login', methods: ['GET', 'POST'])]
    public function loginPage(Request $request): Response
    {
        $session = $request->getSession();
        $error = null;
        $lastEmail = '';

        if ($session->has('user')) {
            $user = $session->get('user');
            $lastEmail = $user['email'] ?? '';
        }

        if ($request->isMethod('POST')) {
            $email = trim((string) $request->request->get('email'));
            $password = (string) $request->request->get('password');
            $lastEmail = $email;

            $validEmail = 'demo@venteticket.mg';
            $validPassword = 'demo123';

            if ($email === $validEmail && $password === $validPassword) {
                $session->set('user', [
                    'email' => $validEmail,
                    'username' => 'Demo Utilisateur',
                ]);

                return $this->redirectToRoute('home');
            }

            $error = 'Identifiants invalides. Utilisez demo@venteticket.mg / demo123.';
        }

        return $this->render('auth/login.html.twig', [
            'last_email' => $lastEmail,
            'error' => $error,
        ]);
    }

    #[Route('/register', name: 'register')]
    public function registerPage(): Response
    {
        // TODO: Implémenter la page d'inscription
        return $this->render('auth/register.html.twig');
    }

    #[Route('/forgot-password', name: 'forgot_password')]
    public function forgotPasswordPage(): Response
    {
        // TODO: Implémenter la page de mot de passe oublié
        return $this->render('auth/forgot_password.html.twig');
    }

    #[Route('/logout', name: 'logout')]
    public function logoutPage(Request $request): Response
    {
        $request->getSession()->remove('user');
        return $this->redirectToRoute('home');
    }

    #[Route('/profile', name: 'profile')]
    public function profilePage(): Response
    {
        // TODO: Implémenter la page de profil
        return $this->render('profile/index.html.twig');
    }

    #[Route('/api/auth/register', name: 'api_auth_register', methods: ['POST'])]
    public function register(Request $request): JsonResponse
    {
        // TODO: Implémenter l'inscription utilisateur
        return new JsonResponse([
            'message' => 'Endpoint d\'inscription - À implémenter',
            'status' => 'success'
        ]);
    }

    #[Route('/api/auth/login', name: 'api_auth_login', methods: ['POST'])]
    public function login(Request $request): JsonResponse
    {
        // TODO: Implémenter la connexion utilisateur avec JWT
        return new JsonResponse([
            'message' => 'Endpoint de connexion - À implémenter',
            'status' => 'success'
        ]);
    }

    #[Route('/api/auth/logout', name: 'api_auth_logout', methods: ['POST'])]
    public function logout(): JsonResponse
    {
        // TODO: Implémenter la déconnexion
        return new JsonResponse([
            'message' => 'Endpoint de déconnexion - À implémenter',
            'status' => 'success'
        ]);
    }

    #[Route('/api/auth/refresh', name: 'api_auth_refresh', methods: ['POST'])]
    public function refreshToken(): JsonResponse
    {
        // TODO: Implémenter le refresh du token JWT
        return new JsonResponse([
            'message' => 'Endpoint de refresh token - À implémenter',
            'status' => 'success'
        ]);
    }

    #[Route('/api/auth/profile', name: 'api_auth_profile', methods: ['GET'])]
    public function getProfile(): JsonResponse
    {
        // TODO: Récupérer le profil de l'utilisateur connecté
        return new JsonResponse([
            'message' => 'Endpoint de profil utilisateur - À implémenter',
            'status' => 'success'
        ]);
    }

    #[Route('/api/auth/profile', name: 'api_auth_update_profile', methods: ['PUT'])]
    public function updateProfile(Request $request): JsonResponse
    {
        // TODO: Mettre à jour le profil utilisateur
        return new JsonResponse([
            'message' => 'Endpoint de mise à jour du profil - À implémenter',
            'status' => 'success'
        ]);
    }
}