<?php

namespace App\EventListener;

use Doctrine\DBAL\Connection;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Environment;

class ThemeListener implements EventSubscriberInterface
{
    public function __construct(
        private readonly Connection $connection,
        private readonly RequestStack $requestStack,
        private readonly Environment $twig
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::CONTROLLER => 'onKernelController',
        ];
    }

    public function onKernelController(ControllerEvent $event): void
    {
        $request = $event->getRequest();
        $session = $request->getSession();
        
        if (!$session->isStarted()) {
            $session->start();
        }

        $sessionUser = $session->get('user');
        $isAuthenticated = is_array($sessionUser) && isset($sessionUser['id']);

        // Récupérer les préférences utilisateur (notamment le thème)
        $preferences = [
            'appearance' => [
                'theme' => 'light', // Par défaut
            ],
        ];

        if ($isAuthenticated) {
            $userId = (int) $sessionUser['id'];
            $preferences = $this->fetchUserPreferences($userId);
        }

        // Passer les préférences à tous les templates via Twig globals
        $this->twig->addGlobal('preferences', $preferences);
        $this->twig->addGlobal('isAuthenticated', $isAuthenticated);
    }

    /**
     * Récupère les préférences utilisateur
     *
     * @return array<string, mixed>
     */
    private function fetchUserPreferences(int $userId): array
    {
        $sql = <<<SQL
            SELECT preference_key, preference_value
            FROM aiolia.user_preferences
            WHERE user_id = :userId
        SQL;

        $rows = $this->connection->executeQuery($sql, ['userId' => $userId])->fetchAllAssociative();

        $preferences = [
            'notifications' => [
                'ticket_alerts' => true,
                'event_reminders' => true,
                'newsletters' => false,
            ],
            'security' => [
                'two_factor_enabled' => false,
            ],
            'appearance' => [
                'theme' => 'light',
            ],
        ];

        foreach ($rows as $row) {
            $key = $row['preference_key'];
            $value = json_decode($row['preference_value'], true);
            
            if ($key === 'notifications') {
                $preferences['notifications'] = array_merge($preferences['notifications'], $value ?? []);
            } elseif ($key === 'security') {
                $preferences['security'] = array_merge($preferences['security'], $value ?? []);
            } elseif ($key === 'appearance') {
                $preferences['appearance'] = array_merge($preferences['appearance'], $value ?? []);
            } else {
                $preferences[$key] = $value;
            }
        }

        return $preferences;
    }
}



