<?php

namespace App\Service;

use App\Repository\TicketInvoiceRepository;
use App\Repository\SubscriptionInvoiceRepository;
use Doctrine\ORM\EntityManagerInterface;

class InvoiceNumberService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private TicketInvoiceRepository $ticketInvoiceRepository,
        private SubscriptionInvoiceRepository $subscriptionInvoiceRepository
    ) {
    }

    /**
     * Génère un numéro de facture unique au format : ANNEE-COMPTEUR
     * Exemple : 2025-00000123
     */
    public function generateInvoiceNumber(string $type = 'ticket'): string
    {
        $year = date('Y');
        $connection = $this->entityManager->getConnection();

        // Récupérer le dernier numéro de facture pour cette année
        if ($type === 'ticket') {
            $lastInvoice = $this->ticketInvoiceRepository->createQueryBuilder('ti')
                ->where('ti.invoiceNumber LIKE :pattern')
                ->setParameter('pattern', $year . '-%')
                ->orderBy('ti.invoiceNumber', 'DESC')
                ->setMaxResults(1)
                ->getQuery()
                ->getOneOrNullResult();
        } else {
            $lastInvoice = $this->subscriptionInvoiceRepository->createQueryBuilder('si')
                ->where('si.invoiceNumber LIKE :pattern')
                ->setParameter('pattern', $year . '-%')
                ->orderBy('si.invoiceNumber', 'DESC')
                ->setMaxResults(1)
                ->getQuery()
                ->getOneOrNullResult();
        }

        // Si aucune facture cette année, commencer à 1
        if (!$lastInvoice) {
            $counter = 1;
        } else {
            // Extraire le compteur du dernier numéro
            $lastNumber = $lastInvoice->getInvoiceNumber();
            $parts = explode('-', $lastNumber);
            $lastCounter = (int) ($parts[1] ?? 0);
            $counter = $lastCounter + 1;
        }

        // Formater le numéro : ANNEE-COMPTEUR (8 chiffres)
        return sprintf('%s-%08d', $year, $counter);
    }

    /**
     * Génère un numéro de facture pour les billets
     */
    public function generateTicketInvoiceNumber(): string
    {
        return $this->generateInvoiceNumber('ticket');
    }

    /**
     * Génère un numéro de facture pour les abonnements
     */
    public function generateSubscriptionInvoiceNumber(): string
    {
        return $this->generateInvoiceNumber('subscription');
    }
}

