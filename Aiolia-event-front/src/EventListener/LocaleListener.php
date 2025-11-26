<?php

namespace App\EventListener;

use Doctrine\DBAL\Connection;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class LocaleListener implements EventSubscriberInterface
{
    public function __construct(
        private readonly Connection $connection
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => [['onKernelRequest', 20]],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();
        
        // Ne pas traiter les requêtes API
        if (str_starts_with($request->getPathInfo(), '/api/')) {
            return;
        }

        $session = $request->getSession();
        
        // Récupérer l'utilisateur depuis la session
        $sessionUser = $session->get('user');
        
        if (is_array($sessionUser) && isset($sessionUser['id'])) {
            $userId = (int) $sessionUser['id'];
            
            // Récupérer le language_code de l'utilisateur depuis la base de données
            try {
                $sql = 'SELECT language_code FROM aiolia.users WHERE id = :userId';
                $result = $this->connection->executeQuery($sql, ['userId' => $userId])->fetchOne();
                
                if ($result) {
                    $languageCode = $result;
                    // Convertir le language_code en locale (fr-FR -> fr, en-US -> en)
                    $locale = $this->convertLanguageCodeToLocale($languageCode);
                    $request->setLocale($locale);
                    $session->set('_locale', $locale);
                }
            } catch (\Exception $e) {
                // En cas d'erreur, utiliser la locale par défaut
                $request->setLocale('fr');
            }
        } else {
            // Utilisateur non connecté, vérifier si une locale est déjà en session
            $sessionLocale = $session->get('_locale');
            if ($sessionLocale) {
                $request->setLocale($sessionLocale);
            } else {
                // Utiliser la locale par défaut
                $request->setLocale('fr');
            }
        }
    }

    /**
     * Convertit un language_code (ex: fr-FR, en-US) en locale (ex: fr, en)
     */
    private function convertLanguageCodeToLocale(string $languageCode): string
    {
        // Extraire la partie langue (avant le tiret)
        $parts = explode('-', $languageCode);
        $lang = strtolower($parts[0] ?? 'fr');
        
        // Mapper les langues supportées
        $supportedLocales = ['fr', 'en', 'mg'];
        
        if (in_array($lang, $supportedLocales)) {
            return $lang;
        }
        
        // Par défaut, retourner 'fr'
        return 'fr';
    }
}



