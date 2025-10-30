<?php

namespace App\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

/**
 * API pour gérer les préférences utilisateur (stockées en localStorage)
 */
#[Route('/api/user/preferences', name: 'api_user_preferences_')]
class UserPreferencesApiController extends AbstractController
{
    /**
     * Récupère les préférences par défaut
     */
    #[Route('/defaults', name: 'defaults', methods: ['GET'])]
    public function getDefaults(): JsonResponse
    {
        return $this->json([
            'success' => true,
            'data' => [
                'theme' => 'light',
                'language' => 'fr',
                'notifications' => [
                    'email' => true,
                    'push' => true,
                    'sms' => false,
                ],
                'marketing_emails' => true,
                'display' => [
                    'events_per_page' => 10,
                    'map_view' => false,
                    'compact_view' => false,
                ],
            ],
        ]);
    }

    /**
     * Valide les préférences envoyées par le client
     */
    #[Route('/validate', name: 'validate', methods: ['POST'])]
    public function validate(Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $data = json_decode($request->getContent(), true);

        $errors = [];

        // Validation du thème
        if (isset($data['theme']) && !in_array($data['theme'], ['light', 'dark', 'auto'])) {
            $errors['theme'] = 'Thème invalide';
        }

        // Validation de la langue
        if (isset($data['language']) && !in_array($data['language'], ['fr', 'en', 'mg'])) {
            $errors['language'] = 'Langue invalide';
        }

        if (count($errors) > 0) {
            return $this->json([
                'success' => false,
                'errors' => $errors,
            ], 400);
        }

        return $this->json([
            'success' => true,
            'message' => 'Préférences valides',
        ]);
    }

    /**
     * Récupère les options disponibles
     */
    #[Route('/options', name: 'options', methods: ['GET'])]
    public function getOptions(): JsonResponse
    {
        return $this->json([
            'success' => true,
            'data' => [
                'themes' => [
                    ['value' => 'light', 'label' => 'Clair'],
                    ['value' => 'dark', 'label' => 'Sombre'],
                    ['value' => 'auto', 'label' => 'Automatique'],
                ],
                'languages' => [
                    ['value' => 'fr', 'label' => 'Français'],
                    ['value' => 'en', 'label' => 'English'],
                    ['value' => 'mg', 'label' => 'Malagasy'],
                ],
                'notification_channels' => [
                    ['value' => 'email', 'label' => 'Email', 'enabled' => true],
                    ['value' => 'push', 'label' => 'Notifications push', 'enabled' => true],
                    ['value' => 'sms', 'label' => 'SMS', 'enabled' => false],
                ],
            ],
        ]);
    }
}

