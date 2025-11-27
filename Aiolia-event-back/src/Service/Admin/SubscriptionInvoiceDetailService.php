<?php

namespace App\Service\Admin;

use App\Entity\SubscriptionInvoice;
use App\Repository\Admin\SubscriptionInvoiceRepository;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Service pour récupérer les détails complets des factures d'abonnement
 * Utilise la vue vw_subscription_invoice_items pour optimiser les performances
 */
class SubscriptionInvoiceDetailService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private SubscriptionInvoiceRepository $invoiceRepository,
    ) {
    }

    /**
     * Récupère tous les détails d'une facture d'abonnement
     * Utilise la vue vw_subscription_invoice_items pour optimiser
     * 
     * @param SubscriptionInvoice $invoice La facture
     * @return array{
     *     invoice: array,
     *     items: array,
     *     plan_info: array|null,
     *     payment_info: array|null,
     *     organizer_info: array|null
     * }
     */
    public function getInvoiceDetails(SubscriptionInvoice $invoice): array
    {
        $connection = $this->entityManager->getConnection();

        // Récupérer les items depuis la vue
        $items = $this->invoiceRepository->getInvoiceItemsForInvoice($invoice);

        // Récupérer les infos du plan
        $planInfo = $this->invoiceRepository->getPlanInfoForInvoice($invoice);

        // Récupérer le mode de paiement
        $paymentMethod = $this->invoiceRepository->getPaymentMethodForInvoice($invoice);

        // Récupérer les infos de l'organisateur depuis la vue
        $organizerInfo = $this->getOrganizerInfoFromView($invoice);

        return [
            'invoice' => [
                'id' => $invoice->getId(),
                'invoice_number' => $invoice->getInvoiceNumber(),
                'status' => $invoice->getStatus(),
                'total_amount' => $invoice->getTotalAmount(),
                'subtotal_amount' => $invoice->getSubtotalAmount(),
                'tax_amount' => $invoice->getTaxAmount(),
                'currency' => $invoice->getCurrency(),
                'issued_at' => $invoice->getIssuedAt()?->format('Y-m-d H:i:s'),
                'due_at' => $invoice->getDueAt()?->format('Y-m-d H:i:s'),
                'paid_at' => $invoice->getPaidAt()?->format('Y-m-d H:i:s'),
            ],
            'items' => $items,
            'plan_info' => $planInfo,
            'payment_info' => $paymentMethod ? ['method' => $paymentMethod] : null,
            'organizer_info' => $organizerInfo,
        ];
    }

    /**
     * Récupère les informations de l'organisateur depuis la vue vw_subscription_invoice_items
     * 
     * @param SubscriptionInvoice $invoice La facture
     * @return array|null
     */
    private function getOrganizerInfoFromView(SubscriptionInvoice $invoice): ?array
    {
        $connection = $this->entityManager->getConnection();

        $sql = "
            SELECT DISTINCT
                organizer_user_id,
                type_organisation,
                statut_verification
            FROM aiolia.vw_subscription_invoice_items
            WHERE invoice_id = :invoice_id
            LIMIT 1
        ";

        $result = $connection->fetchAssociative($sql, [
            'invoice_id' => $invoice->getId(),
        ]);

        return $result ?: null;
    }

    /**
     * Récupère les détails de plusieurs factures en une seule requête
     * 
     * @param array $invoices Tableau de SubscriptionInvoice
     * @return array invoice_id => details
     */
    public function getMultipleInvoiceDetails(array $invoices): array
    {
        if (empty($invoices)) {
            return [];
        }

        $invoiceIds = array_map(fn($invoice) => $invoice->getId(), $invoices);
        $connection = $this->entityManager->getConnection();

        $placeholders = implode(',', array_fill(0, count($invoiceIds), '?'));
        
        // Récupérer tous les items depuis la vue
        $sql = "
            SELECT 
                invoice_id,
                item_id,
                plan_code,
                plan_name,
                description,
                quantite,
                prix_unitaire,
                montant_total,
                organizer_user_id,
                type_organisation,
                statut_verification
            FROM aiolia.vw_subscription_invoice_items
            WHERE invoice_id IN ($placeholders)
            ORDER BY invoice_id, item_id ASC
        ";

        $rows = $connection->fetchAllAssociative($sql, $invoiceIds);

        // Grouper par facture
        $details = [];
        foreach ($rows as $row) {
            $invoiceId = $row['invoice_id'];
            
            if (!isset($details[$invoiceId])) {
                $details[$invoiceId] = [
                    'items' => [],
                    'organizer_info' => null,
                ];
            }

            $details[$invoiceId]['items'][] = [
                'item_id' => $row['item_id'],
                'plan_code' => $row['plan_code'],
                'plan_name' => $row['plan_name'],
                'description' => $row['description'],
                'quantite' => (int) $row['quantite'],
                'prix_unitaire' => (float) $row['prix_unitaire'],
                'montant_total' => (float) $row['montant_total'],
            ];

            // Stocker les infos de l'organisateur (seront les mêmes pour tous les items)
            if ($details[$invoiceId]['organizer_info'] === null && $row['organizer_user_id']) {
                $details[$invoiceId]['organizer_info'] = [
                    'organizer_user_id' => $row['organizer_user_id'],
                    'type_organisation' => $row['type_organisation'],
                    'statut_verification' => $row['statut_verification'],
                ];
            }
        }

        // Ajouter les infos de plan pour chaque facture
        $planInfos = $this->invoiceRepository->getPlanInfosForInvoices($invoices);
        foreach ($planInfos as $invoiceId => $planInfo) {
            if (isset($details[$invoiceId])) {
                $details[$invoiceId]['plan_info'] = $planInfo;
            }
        }

        return $details;
    }
}

