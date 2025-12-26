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
     * Optimisé : Utilise directement les tables avec des jointures efficaces
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
                sp.periode_facturation as billing_period,
                os.statut as subscription_status,
                os.mois_prepayes_restants,
                os.mis_en_pause_le,
                os.repris_le
            FROM aiolia.abonnements_organisateurs os
            INNER JOIN aiolia.profils_organisateurs op ON op.id = os.id_profil_organisateur
            INNER JOIN aiolia.plans_abonnements sp ON sp.id = os.id_plan
            WHERE os.statut IN ('active', 'paused')
                AND sp.est_actif = true
                AND os.annule_le IS NULL
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

    /**
     * Récupère l'abonnement actif d'un utilisateur
     * 
     * @param int $userId ID de l'utilisateur
     * @return array{id: int, fin_periode_courante: string|null}|null
     */
    public function findActiveSubscriptionByUserId(int $userId): ?array
    {
        $connection = $this->registry->getConnection();
        
        $sql = "
            SELECT os.id, os.fin_periode_courante
            FROM aiolia.abonnements_organisateurs os
            INNER JOIN aiolia.profils_organisateurs po ON po.id = os.id_profil_organisateur
            WHERE po.id_utilisateur = :userId
                AND os.statut = 'active'
                AND os.annule_le IS NULL
            ORDER BY os.cree_le DESC
            LIMIT 1
        ";
        
        $result = $connection->fetchAssociative($sql, ['userId' => $userId]);
        
        return $result ?: null;
    }

    /**
     * Récupère le prochain mois de facturation depuis les factures non payées
     * 
     * @param int $subscriptionId ID de l'abonnement
     * @return string|null Date au format Y-m-d ou null
     */
    public function findNextUnpaidInvoiceMonth(int $subscriptionId): ?string
    {
        $connection = $this->registry->getConnection();
        
        $sql = "
            SELECT mois_facturation
            FROM aiolia.factures_abonnements
            WHERE id_abonnement = :subscriptionId
                AND statut IN ('issued', 'draft', 'pending')
                AND mois_facturation >= DATE_TRUNC('month', CURRENT_DATE)
            ORDER BY mois_facturation ASC
            LIMIT 1
        ";
        
        $result = $connection->fetchOne($sql, ['subscriptionId' => $subscriptionId]);
        
        return $result ?: null;
    }

    /**
     * Récupère le prochain mois de facturation depuis les factures en retard
     * 
     * @param int $subscriptionId ID de l'abonnement
     * @return string|null Date au format Y-m-d ou null
     */
    public function findNextOverdueInvoiceMonth(int $subscriptionId): ?string
    {
        $connection = $this->registry->getConnection();
        
        $sql = "
            SELECT mois_facturation
            FROM aiolia.factures_abonnements
            WHERE id_abonnement = :subscriptionId
                AND statut = 'overdue'
                AND payee_le IS NULL
                AND mois_facturation >= DATE_TRUNC('month', CURRENT_DATE)
            ORDER BY mois_facturation ASC
            LIMIT 1
        ";
        
        $result = $connection->fetchOne($sql, ['subscriptionId' => $subscriptionId]);
        
        return $result ?: null;
    }

    /**
     * Récupère le dernier mois de facturation payé
     * 
     * @param int $subscriptionId ID de l'abonnement
     * @return string|null Date au format Y-m-d ou null
     */
    public function findLastPaidInvoiceMonth(int $subscriptionId): ?string
    {
        $connection = $this->registry->getConnection();
        
        $sql = "
            SELECT mois_facturation
            FROM aiolia.factures_abonnements
            WHERE id_abonnement = :subscriptionId
                AND statut = 'paid'
            ORDER BY mois_facturation DESC
            LIMIT 1
        ";
        
        $result = $connection->fetchOne($sql, ['subscriptionId' => $subscriptionId]);
        
        return $result ?: null;
    }

    /**
     * Vérifie s'il existe une facture pour un mois donné
     * 
     * @param int $subscriptionId ID de l'abonnement
     * @param string $month Date au format Y-m-01
     * @return array{id: int, statut: string, payee_le: string|null}|null
     */
    public function findInvoiceForMonth(int $subscriptionId, string $month): ?array
    {
        $connection = $this->registry->getConnection();
        
        $sql = "
            SELECT id, statut, payee_le
            FROM aiolia.factures_abonnements
            WHERE id_abonnement = :subscriptionId
                AND mois_facturation = :checkMonth
            LIMIT 1
        ";
        
        $result = $connection->fetchAssociative($sql, [
            'subscriptionId' => $subscriptionId,
            'checkMonth' => $month
        ]);
        
        return $result ?: null;
    }
}

