<?php

namespace App\Service\Admin;

use App\Entity\SubscriptionInvoice;
use App\Repository\Admin\SubscriptionInvoiceRepository;
use Doctrine\ORM\EntityManagerInterface;

class BillingInvoiceDetailService
{
    public function __construct(
        private SubscriptionInvoiceRepository $subscriptionInvoiceRepository,
        private EntityManagerInterface $entityManager
    ) {
    }

    /**
     * Retourne les informations complètes du plan associé à une facture.
     */
    public function getPlanInfo(SubscriptionInvoice $invoice): ?array
    {
        return $this->subscriptionInvoiceRepository->getPlanInfoForInvoice($invoice);
    }

    /**
     * Retourne les lignes détaillées (description, quantité, prix) d'une facture d'abonnement.
     */
    public function getInvoiceItems(SubscriptionInvoice $invoice): array
    {
        return $this->subscriptionInvoiceRepository->getInvoiceItemsForInvoice($invoice);
    }

    /**
     * Retourne le mode de paiement pour une facture.
     * Récupère depuis la colonne methode_paiement de la facture ou depuis la table paiements_abonnements.
     */
    public function getPaymentMethod(SubscriptionInvoice $invoice): ?string
    {
        $connection = $this->entityManager->getConnection();
        
        // Récupérer directement depuis la base de données
        // D'abord depuis la colonne methode_paiement de la facture, sinon depuis paiements_abonnements
        $sql = "
            SELECT 
                COALESCE(
                    fa.methode_paiement,
                    (SELECT pa.fournisseur 
                     FROM aiolia.paiements_abonnements pa 
                     WHERE pa.id_facture = fa.id 
                     ORDER BY pa.paye_le DESC NULLS LAST, pa.cree_le DESC 
                     LIMIT 1)
                ) as methode_paiement
            FROM aiolia.factures_abonnements fa
            WHERE fa.id = :invoice_id
        ";
        
        $result = $connection->fetchOne($sql, ['invoice_id' => $invoice->getId()]);
        
        return $result !== false && $result !== null ? $result : null;
    }
}

