<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Http\Event\LogoutEvent;

/**
 * Nettoie les flash messages lors de la déconnexion
 */
class LogoutFlashCleanerSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private RequestStack $requestStack
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            LogoutEvent::class => 'onLogout',
        ];
    }

    public function onLogout(LogoutEvent $event): void
    {
        $request = $this->requestStack->getCurrentRequest();
        
        if ($request && $request->hasSession()) {
            $session = $request->getSession();
            
            // Nettoyer tous les flash messages
            $session->getBag('flashes')->clear();
            
            // Optionnel : invalider complètement la session
            // $session->invalidate();
        }
    }
}
