<?php

namespace App\Repository\Admin;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Repository pour les abonnements organisateurs
 * Utilise des requêtes SQL directes car il n'y a pas d'entité Doctrine correspondante
 */
class OrganizerSubscriptionRepository
{
    public function __construct(
        private ManagerRegistry $registry
    ) {
    }

    /**
     * Récupère tous les abonnements actifs pour la génération de factures
     * 
     * @return array{subscription_id: int, organizer_profile_id: int, user_id: int, plan_id: int, price: float, vat_rate: float, currency: string, billing_period: string}[]
     */
    public function findActiveSubscriptionsForInvoiceGeneration(): array
    {
        $connection = $this->registry->getConnection();
        
        $sql = "
            SELECT 
                os.id as subscription_id,
                os.id_profil_organisateur as organizer_profile_id,
                op.id_utilisateur as user_id,
                sp.id as plan_id,
                sp.prix as price,
                sp.taux_tva as vat_rate,
                sp.devise as currency,
                sp.periode_facturation as billing_period
            FROM aiolia.abonnements_organisateurs os
            INNER JOIN aiolia.profils_organisateurs op ON op.id = os.id_profil_organisateur
            INNER JOIN aiolia.plans_abonnements sp ON sp.id = os.id_plan
            WHERE os.statut = 'active'
                AND sp.est_actif = true
                AND os.annule_le IS NULL
                AND (os.annuler_a_la_fin_periode = false OR os.renouvellement_le > CURRENT_DATE)
        ";
        
        return $connection->fetchAllAssociative($sql);
    }

    /**
     * Met à jour le statut d'un abonnement en pause
     * 
     * @param int $subscriptionId ID de l'abonnement
     * @param \DateTimeInterface $pausedDate Date de mise en pause
     * @param array $metadata Métadonnées additionnelles
     */
    public function pauseSubscription(int $subscriptionId, \DateTimeInterface $pausedDate, array $metadata = []): void
    {
        $connection = $this->registry->getConnection();
        
        $sql = "
            UPDATE aiolia.abonnements_organisateurs
            SET statut = 'paused',
                mis_en_pause_le = :paused_date,
                modifie_le = CURRENT_TIMESTAMP,
                metadonnees = COALESCE(metadonnees, '{}'::jsonb) || :metadata::jsonb
            WHERE id = :subscription_id
        ";
        
        $connection->executeStatement($sql, [
            'subscription_id' => $subscriptionId,
            'paused_date' => $pausedDate,
            'metadata' => json_encode($metadata),
        ]);
    }

    /**
     * Récupère les informations d'un abonnement
     * 
     * @param int $subscriptionId ID de l'abonnement
     * @return array{id: int, id_profil_organisateur: int, id_utilisateur: int, statut: string, id_plan: int}|null
     */
    public function findSubscription(int $subscriptionId): ?array
    {
        $connection = $this->registry->getConnection();
        
        $sql = "
            SELECT 
                os.id,
                os.id_profil_organisateur,
                op.id_utilisateur,
                os.statut,
                os.id_plan
            FROM aiolia.abonnements_organisateurs os
            INNER JOIN aiolia.profils_organisateurs op ON op.id = os.id_profil_organisateur
            WHERE os.id = :subscription_id
        ";
        
        $result = $connection->fetchAssociative($sql, ['subscription_id' => $subscriptionId]);
        
        return $result ?: null;
    }

    /**
     * Met à jour le crédit prépayé dans l'abonnement
     * 
     * @param int $subscriptionId ID de l'abonnement
     * @param int $numberOfMonths Nombre de mois à ajouter
     */
    public function updatePrepaidCredit(int $subscriptionId, int $numberOfMonths): void
    {
        $connection = $this->registry->getConnection();
        
        $sql = "
            UPDATE aiolia.abonnements_organisateurs
            SET mois_prepayes_restants = mois_prepayes_restants + :months,
                modifie_le = CURRENT_TIMESTAMP
            WHERE id = :subscription_id
        ";
        
        $connection->executeStatement($sql, [
            'subscription_id' => $subscriptionId,
            'months' => $numberOfMonths,
        ]);
    }

    /**
     * Récupère le nombre de mois prépayés restants
     * 
     * @param int $subscriptionId ID de l'abonnement
     * @return int
     */
    public function getRemainingPrepaidMonths(int $subscriptionId): int
    {
        $connection = $this->registry->getConnection();
        
        $sql = "
            SELECT mois_prepayes_restants
            FROM aiolia.abonnements_organisateurs
            WHERE id = :subscription_id
        ";
        
        $result = $connection->fetchOne($sql, ['subscription_id' => $subscriptionId]);
        
        return (int) ($result ?? 0);
    }
}

