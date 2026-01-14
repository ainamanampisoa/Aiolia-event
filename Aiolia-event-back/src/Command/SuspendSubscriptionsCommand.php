<?php

namespace App\Command;

use App\Entity\OrganizerSubscription;
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
    name: 'app:suspend-subscriptions',
    description: 'Suspend les abonnements avec factures impayées depuis 10 jours (J+10) - À exécuter tous les jours à 18h',
)]
class SuspendSubscriptionsCommand extends Command
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
        $io->title('Suspension des abonnements (J+10)');

        try {
            $now = new \DateTimeImmutable();
            
            // Trouver les factures impayées depuis 10 jours
            $invoices = $this->findOverdueInvoices(10, $now);

            if (empty($invoices)) {
                $io->info('Aucune facture en retard de 10 jours trouvée.');
                return Command::SUCCESS;
            }

            $io->info(sprintf('Traitement de %d facture(s)...', count($invoices)));

            $suspended = 0;
            $notifications = 0;
            $errors = 0;

            foreach ($invoices as $invoice) {
                try {
                    $subscriptionId = (int) $invoice->getSubscriptionId();
                    $subscription = $this->entityManager->getRepository(OrganizerSubscription::class)->find($subscriptionId);

                    if (!$subscription) {
                        $io->warning("Abonnement {$subscriptionId} non trouvé pour la facture {$invoice->getInvoiceNumber()}");
                        $errors++;
                        continue;
                    }

                    // Vérifier si l'abonnement n'est pas déjà suspendu
                    if ($subscription->getStatut() === OrganizerSubscription::STATUS_PAUSED) {
                        $io->info("Abonnement {$subscriptionId} déjà suspendu");
                        continue;
                    }

                    // Suspendre l'abonnement
                    $subscription->setStatut(OrganizerSubscription::STATUS_PAUSED);
                    $subscription->setMisEnPauseLe(new \DateTime());
                    
                    // Mettre à jour les métadonnées
                    $metadata = $subscription->getMetadonnees() ?? [];
                    $metadata['suspended_reason'] = 'unpaid_invoice';
                    $metadata['suspended_at'] = (new \DateTimeImmutable())->format('Y-m-d H:i:s');
                    $metadata['invoice_number'] = $invoice->getInvoiceNumber();
                    $subscription->setMetadonnees($metadata);

                    $this->entityManager->flush();
                    $suspended++;

                    // Envoyer la notification de suspension
                    $customer = $invoice->getCustomer();
                    $this->notificationService->sendSuspensionNotification($customer, $subscription, $invoice);
                    $notifications++;

                    $io->writeln("  ✓ Abonnement {$subscriptionId} suspendu (facture {$invoice->getInvoiceNumber()})");
                } catch (\Exception $e) {
                    $errors++;
                    $io->error("Erreur pour la facture {$invoice->getInvoiceNumber()}: " . $e->getMessage());
                }
            }

            $io->success(sprintf(
                'Traitement terminé : %d abonnement(s) suspendu(s), %d notification(s) envoyée(s), %d erreur(s)',
                $suspended,
                $notifications,
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

