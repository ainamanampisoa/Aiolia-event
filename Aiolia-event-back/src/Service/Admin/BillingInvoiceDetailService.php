<?php

namespace App\Service\Admin;

use App\Entity\SubscriptionInvoice;
use App\Repository\Admin\SubscriptionInvoiceRepository;

class BillingInvoiceDetailService
{
    public function __construct(
        private SubscriptionInvoiceRepository $subscriptionInvoiceRepository
    ) {}

    public function getPlanInfo(SubscriptionInvoice $invoice): ?array
    {
        return $this->subscriptionInvoiceRepository->getPlanInfoForInvoice($invoice);
    }

    public function getInvoiceItems(SubscriptionInvoice $invoice): array
    {
        return $this->subscriptionInvoiceRepository->getInvoiceItemsForInvoice($invoice);
    }

    public function getPaymentMethod(SubscriptionInvoice $invoice): ?string
    {
        return $this->subscriptionInvoiceRepository->getPaymentMethodForInvoice($invoice);
    }
}
