<?php

namespace App\EventListener;

use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class PromoRequestListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 1000],
            KernelEvents::CONTROLLER => ['onKernelController', 10],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();
        
        // Capturer toutes les requêtes vers /api/tickets/promo
        if (strpos($request->getPathInfo(), '/api/tickets/promo') !== false) {
            $logDir = dirname(__DIR__, 2) . '/var/log';
            @mkdir($logDir, 0755, true);
            $logFile = $logDir . '/promo_validation.log';
            
            $logEntry = date('Y-m-d H:i:s') . " - [EVENT LISTENER] Requête capturée\n";
            $logEntry .= "Path: " . $request->getPathInfo() . "\n";
            $logEntry .= "Method: " . $request->getMethod() . "\n";
            $logEntry .= "URI: " . $request->getRequestUri() . "\n";
            $logEntry .= "Content-Type: " . $request->headers->get('Content-Type', 'N/A') . "\n";
            $logEntry .= "Access-Control-Request-Method: " . $request->headers->get('Access-Control-Request-Method', 'N/A') . "\n";
            $logEntry .= "Origin: " . $request->headers->get('Origin', 'N/A') . "\n";
            $logEntry .= "Referer: " . $request->headers->get('Referer', 'N/A') . "\n";
            $logEntry .= "Firewall: " . ($request->attributes->get('_firewall_context', 'N/A')) . "\n";
            $logEntry .= "Route: " . ($request->attributes->get('_route', 'N/A')) . "\n";
            $logEntry .= "Body: " . substr($request->getContent(), 0, 200) . "\n";
            $logEntry .= "Event Type: " . ($event->isMainRequest() ? 'MAIN' : 'SUB') . "\n";
            $logEntry .= str_repeat('-', 80) . "\n";
            
            @file_put_contents($logFile, $logEntry, FILE_APPEND);
            error_log('[PROMO LISTENER] Request captured: ' . $request->getMethod() . ' ' . $request->getPathInfo());
        }
    }
    
    public function onKernelController(ControllerEvent $event): void
    {
        $request = $event->getRequest();
        
        // Capturer quand le contrôleur est appelé
        if (strpos($request->getPathInfo(), '/api/tickets/promo') !== false) {
            $logDir = dirname(__DIR__, 2) . '/var/log';
            @mkdir($logDir, 0755, true);
            $logFile = $logDir . '/promo_validation.log';
            
            $controller = $event->getController();
            $controllerInfo = is_array($controller) 
                ? (is_object($controller[0]) ? get_class($controller[0]) : 'N/A') . '::' . ($controller[1] ?? 'N/A')
                : 'N/A';
            
            $logEntry = date('Y-m-d H:i:s') . " - [CONTROLLER EVENT] Contrôleur appelé\n";
            $logEntry .= "Controller: " . $controllerInfo . "\n";
            $logEntry .= "Route: " . ($request->attributes->get('_route', 'N/A')) . "\n";
            $logEntry .= "Method: " . $request->getMethod() . "\n";
            $logEntry .= str_repeat('-', 80) . "\n";
            
            @file_put_contents($logFile, $logEntry, FILE_APPEND);
            error_log('[PROMO CONTROLLER] Controller called: ' . $controllerInfo);
        }
    }
}
