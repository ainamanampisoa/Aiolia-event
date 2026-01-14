<?php

namespace App\Command;

use App\Entity\SubscriptionInvoice;
use App\Repository\Admin\SubscriptionInvoiceRepository;
use App\Service\Organisateur\SubscriptionNotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:overdue-invoice-warning',
    description: 'Envoie un avertissement pour les factures impayées depuis 4 jours (J+4) - À exécuter tous les jours à 9h',
)]
class OverdueInvoiceWarningCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private SubscriptionInvoiceRepository $invoiceRepository,
        private SubscriptionNotificationService $notificationService,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Avertissement factures impayées (J+4)');

        try {
            $now = new \DateTimeImmutable();
            
            // Trouver les factures avec statut "issued" ou "pending" dont la date d'échéance est dépassée de 4 jours exactement
            $invoices = $this->findOverdueInvoices(4, $now);

            if (empty($invoices)) {
                $io->info('Aucune facture en retard de 4 jours trouvée.');
                return Command::SUCCESS;
            }

            $io->info(sprintf('Traitement de %d facture(s)...', count($invoices)));

            $sent = 0;
            $errors = 0;
            $marked = 0;

            foreach ($invoices as $invoice) {
                try {
                    // Marquer la facture comme "overdue" si elle ne l'est pas déjà
                    if ($invoice->getStatus() !== SubscriptionInvoice::STATUS_OVERDUE) {
                        $invoice->setStatus(SubscriptionInvoice::STATUS_OVERDUE);
                        $this->entityManager->flush();
                        $marked++;
                    }

                    // Envoyer l'avertissement
                    $this->notificationService->sendOverdueWarning($invoice);
                    $sent++;
                } catch (\Exception $e) {
                    $errors++;
                    $io->error("Erreur pour la facture {$invoice->getInvoiceNumber()}: " . $e->getMessage());
                }
            }

            $io->success(sprintf(
                'Traitement terminé : %d facture(s) marquée(s) comme overdue, %d avertissement(s) envoyé(s), %d erreur(s)',
                $marked,
                $sent,
                $errors
            ));

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Erreur lors du traitement : ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * Trouve les factures impayées depuis exactement N jours
     */
    private function findOverdueInvoices(int $daysOverdue, \DateTimeImmutable $now): array
    {
        $targetDate = $now->modify("-{$daysOverdue} days");
        $targetDateStart = $targetDate->setTime(0, 0, 0);
        $targetDateEnd = $targetDate->setTime(23, 59, 59);

        return $this->invoiceRepository->createQueryBuilder('si')
            ->where('si.status IN (:statuses)')
            ->andWhere('si.dueAt >= :dateStart')
            ->andWhere('si.dueAt <= :dateEnd')
            ->andWhere('si.paidAt IS NULL')
            ->setParameter('statuses', [SubscriptionInvoice::STATUS_ISSUED, SubscriptionInvoice::STATUS_DRAFT])
            ->setParameter('dateStart', $targetDateStart)
            ->setParameter('dateEnd', $targetDateEnd)
            ->getQuery()
            ->getResult();
    }
}

