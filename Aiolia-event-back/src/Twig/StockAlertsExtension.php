<?php

namespace App\Twig;

use App\Entity\User;
use App\Service\Organisateur\TypeBilletService;
use Symfony\Bundle\SecurityBundle\Security;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class StockAlertsExtension extends AbstractExtension
{
    public function __construct(
        private TypeBilletService $typeBilletService,
        private Security $security
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('has_stock_alerts_for_active_events', [$this, 'hasStockAlertsForActiveEvents']),
        ];
    }

    public function hasStockAlertsForActiveEvents(): bool
    {
        $user = $this->security->getUser();
        
        if (!$user instanceof User) {
            return false;
        }

        $typesBillets = $this->typeBilletService->getByOrganizer($user);
        $timezone = new \DateTimeZone('Indian/Antananarivo');
        $now = new \DateTime('now', $timezone);

        foreach ($typesBillets as $typeBillet) {
            $evenement = $typeBillet->getEvenement();
            
            // Filtrer uniquement les événements en cours et à venir
            if ($evenement && $evenement->getCommenceLe()) {
                $commenceLe = clone $evenement->getCommenceLe();
                if ($commenceLe->getTimezone()->getName() !== $timezone->getName()) {
                    $commenceLe->setTimezone($timezone);
                }
                
                $seTermineLe = $evenement->getSeTermineLe();
                if ($seTermineLe) {
                    $seTermineLe = clone $seTermineLe;
                    if ($seTermineLe->getTimezone()->getName() !== $timezone->getName()) {
                        $seTermineLe->setTimezone($timezone);
                    }
                }
                
                // Vérifier si l'événement est en cours ou à venir
                $isOngoing = $commenceLe <= $now && ($seTermineLe === null || $seTermineLe >= $now);
                $isUpcoming = $commenceLe > $now;
                
                // Ignorer les événements passés
                if (!$isOngoing && !$isUpcoming) {
                    continue;
                }
            }
            
            $inventaire = $typeBillet->getInventaire();
            if ($inventaire) {
                $quantiteRestante = $inventaire->getQuantiteTotale() - $inventaire->getQuantiteVendue() - $inventaire->getQuantiteReservee();
                $pourcentage = $inventaire->getQuantiteTotale() > 0
                    ? ($quantiteRestante / $inventaire->getQuantiteTotale()) * 100
                    : 0;

                if ($pourcentage <= 10) {
                    return true;
                }
            }
        }

        return false;
    }
}

