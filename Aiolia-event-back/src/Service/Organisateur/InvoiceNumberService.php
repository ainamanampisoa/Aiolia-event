<?php

namespace App\Service\Organisateur;

use App\Repository\Organisateur\TicketInvoiceRepository;
use App\Repository\Admin\SubscriptionInvoiceRepository;
use Doctrine\ORM\EntityManagerInterface;

class InvoiceNumberService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private TicketInvoiceRepository $ticketInvoiceRepository,
        private SubscriptionInvoiceRepository $subscriptionInvoiceRepository
    ) {
    }

    
    public function generateInvoiceNumber(string $type = 'ticket'): string
    {
        $year = date('Y');
        $connection = $this->entityManager->getConnection();

        
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

        
        if (!$lastInvoice) {
            $counter = 1;
        } else {
            
            $lastNumber = $lastInvoice->getInvoiceNumber();
            $parts = explode('-', $lastNumber);
            $lastCounter = (int) ($parts[1] ?? 0);
            $counter = $lastCounter + 1;
        }

        
        return sprintf('%s-%08d', $year, $counter);
    }

    
    public function generateTicketInvoiceNumber(): string
    {
        return $this->generateInvoiceNumber('ticket');
    }

    
    public function generateSubscriptionInvoiceNumber(): string
    {
        return $this->generateInvoiceNumber('subscription');
    }

    /**
     * Génère plusieurs numéros de facture consécutifs en une seule fois
     * Utile pour créer plusieurs factures (trimestriel, annuel) sans risque de doublons
     */
    public function generateMultipleSubscriptionInvoiceNumbers(int $count): array
    {
        $year = date('Y');
        $connection = $this->entityManager->getConnection();

        // Récupérer le dernier numéro de facture
        $lastInvoice = $this->subscriptionInvoiceRepository->createQueryBuilder('si')
            ->where('si.invoiceNumber LIKE :pattern')
            ->setParameter('pattern', $year . '-%')
            ->orderBy('si.invoiceNumber', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        // Déterminer le compteur de départ
        if (!$lastInvoice) {
            $startCounter = 1;
        } else {
            $lastNumber = $lastInvoice->getInvoiceNumber();
            $parts = explode('-', $lastNumber);
            $lastCounter = (int) ($parts[1] ?? 0);
            $startCounter = $lastCounter + 1;
        }

        // Générer les numéros consécutifs
        $invoiceNumbers = [];
        for ($i = 0; $i < $count; $i++) {
            $counter = $startCounter + $i;
            $invoiceNumbers[] = sprintf('%s-%08d', $year, $counter);
        }

        return $invoiceNumbers;
    }
}

