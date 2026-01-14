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
    name: 'app:final-reminder',
    description: 'Envoie le dernier rappel pour les factures impayées depuis 7 jours (J+7) - À exécuter tous les jours à 14h',
)]
class FinalReminderCommand extends Command
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
        $io->title('Dernier rappel avant suspension (J+7)');

        try {
            $now = new \DateTimeImmutable();
            
            // Trouver les factures impayées depuis 7 jours
            $invoices = $this->findOverdueInvoices(7, $now);

            if (empty($invoices)) {
                $io->info('Aucune facture en retard de 7 jours trouvée.');
                return Command::SUCCESS;
            }

            $io->info(sprintf('Traitement de %d facture(s)...', count($invoices)));

            $sent = 0;
            $errors = 0;

            foreach ($invoices as $invoice) {
                try {
                    // S'assurer que la facture est marquée comme overdue
                    if ($invoice->getStatus() !== SubscriptionInvoice::STATUS_OVERDUE) {
                        $invoice->setStatus(SubscriptionInvoice::STATUS_OVERDUE);
                        $this->entityManager->flush();
                    }

                    // Envoyer le dernier rappel
                    $this->notificationService->sendFinalReminder($invoice);
                    $sent++;
                } catch (\Exception $e) {
                    $errors++;
                    $io->error("Erreur pour la facture {$invoice->getInvoiceNumber()}: " . $e->getMessage());
                }
            }

            $io->success(sprintf(
                'Traitement terminé : %d rappel(s) envoyé(s), %d erreur(s)',
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
            ->where('si.status = :status')
            ->andWhere('si.dueAt >= :dateStart')
            ->andWhere('si.dueAt <= :dateEnd')
            ->andWhere('si.paidAt IS NULL')
            ->setParameter('status', SubscriptionInvoice::STATUS_OVERDUE)
            ->setParameter('dateStart', $targetDateStart)
            ->setParameter('dateEnd', $targetDateEnd)
            ->getQuery()
            ->getResult();
    }
}

