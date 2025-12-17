<?php

namespace App\Service\Organisateur;

use App\Repository\Organisateur\EventStatsRepository;
use App\Entity\OrganizerProfile;
use DateTimeImmutable;

class EventStatsService
{
    public function __construct(private readonly EventStatsRepository $statsRepository)
    {
    }

    /**
     * Statistiques d'affichage / engagement par événement
     */
    public function getViewsCountByEventIds(array $eventIds): array
    {
        return $this->statsRepository->getViewsCountByEventIds($eventIds);
    }

    public function getFavoritesCountByEventIds(array $eventIds): array
    {
        return $this->statsRepository->getFavoritesCountByEventIds($eventIds);
    }

    public function getParticipantsCountByEventIds(array $eventIds): array
    {
        return $this->statsRepository->getParticipantsCountByEventIds($eventIds);
    }

    public function getMaxUserCount(): int
    {
        return $this->statsRepository->getMaxUserCount();
    }

    /**
     * Dépense d'abonnement TTC du mois courant pour un organisateur donné
     */
    public function getCurrentMonthSubscriptionExpense(OrganizerProfile $organizerProfile): float
    {
        $id = $organizerProfile->getId();
        if ($id === null) {
            return 0.0;
        }

        return $this->statsRepository->getCurrentMonthSubscriptionExpenseForOrganizer((int) $id);
    }

    /**
     * Évolution des ventes dans le temps
     */
    public function getSalesEvolutionOverTime(array $eventIds, ?\DateTimeInterface $dateFrom = null, ?\DateTimeInterface $dateTo = null): array
    {
        return $this->statsRepository->getSalesEvolutionOverTime($eventIds, $dateFrom, $dateTo);
    }

    /**
     * Répartition des ventes par type de billet (catégorie)
     */
    public function getSalesDistributionByTicketType(array $eventIds, ?\DateTimeInterface $dateFrom = null, ?\DateTimeInterface $dateTo = null): array
    {
        return $this->statsRepository->getSalesDistributionByTicketType($eventIds, $dateFrom, $dateTo);
    }

    /**
     * Comparaison des ventes par type de billet (nom)
     */
    public function getSalesComparisonByTicketType(array $eventIds, ?\DateTimeInterface $dateFrom = null, ?\DateTimeInterface $dateTo = null): array
    {
        return $this->statsRepository->getSalesComparisonByTicketType($eventIds, $dateFrom, $dateTo);
    }

    /**
     * Taux de remplissage (occupation) par événement
     */
    public function getTicketOccupancyRateByEventIds(array $eventIds): array
    {
        return $this->statsRepository->getTicketOccupancyRateByEventIds($eventIds);
    }

}
