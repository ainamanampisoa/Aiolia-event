<?php

namespace App\Service;

use App\Entity\SubscriptionInvoice;
use App\Repository\SubscriptionInvoiceRepository;

class BillingInvoiceDetailService
{
    public function __construct(
        private SubscriptionInvoiceRepository $subscriptionInvoiceRepository
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
}

