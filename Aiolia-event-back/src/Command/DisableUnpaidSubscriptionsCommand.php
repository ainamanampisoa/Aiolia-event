<?php

namespace App\Command;

use App\Service\SubscriptionInvoiceGenerationService;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:disable-unpaid-subscriptions',
    description: 'Désactive les abonnements dont les factures ne sont pas payées après le 13ème jour du mois',
)]
class DisableUnpaidSubscriptionsCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ?LoggerInterface $logger = null,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $today = new \DateTimeImmutable();
        $dayOfMonth = (int) $today->format('d');

        // Vérifier si on est après le 13ème jour du mois
        if ($dayOfMonth < 13) {
            $io->warning(sprintf(
                'Cette commande doit être exécutée à partir du 13ème jour du mois. Aujourd\'hui est le jour %d.',
                $dayOfMonth
            ));
            
            if (!$io->confirm('Voulez-vous quand même continuer ?', false)) {
                return Command::SUCCESS;
            }
        }

        $io->title('Désactivation des abonnements non payés');

        $currentMonth = (int) $today->format('n');
        $currentYear = (int) $today->format('Y');
        $firstDayOfMonth = new \DateTimeImmutable(sprintf('%d-%d-01 00:00:00', $currentYear, $currentMonth));
        $lastDayOfMonth = new \DateTimeImmutable(sprintf('%d-%d-%d 23:59:59', $currentYear, $currentMonth, (int) $firstDayOfMonth->format('t')));

        $connection = $this->entityManager->getConnection();
        
        // Récupérer les abonnements avec factures non payées du mois courant
        // Uniquement ceux dont la facture n'est pas payée après le 13ème jour
        $sql = "
            SELECT DISTINCT 
                os.id as subscription_id, 
                os.id_profil_organisateur, 
                si.id as invoice_id, 
                si.numero_facture
            FROM aiolia.abonnements_organisateurs os
            INNER JOIN aiolia.factures_abonnements si ON si.id_abonnement = os.id
            WHERE os.statut = 'active'::subscription_status_enum
                AND si.emise_le >= :monthStart
                AND si.emise_le <= :monthEnd
                AND si.statut IN ('issued', 'draft', 'overdue')
                AND si.payee_le IS NULL
        ";

        $subscriptions = $connection->fetchAllAssociative($sql, [
            'monthStart' => $firstDayOfMonth,
            'monthEnd' => $lastDayOfMonth,
        ]);

        $disabled = 0;
        $errors = [];

        foreach ($subscriptions as $subscription) {
            try {
                // Mettre en pause l'abonnement (mettre le statut à 'paused')
                $updateSql = "
                    UPDATE aiolia.abonnements_organisateurs 
                    SET statut = 'paused'::subscription_status_enum,
                        mis_en_pause_le = :now,
                        modifie_le = :now
                    WHERE id = :subscription_id
                ";
                
                $connection->executeStatement($updateSql, [
                    'subscription_id' => $subscription['subscription_id'],
                    'now' => $today,
                ]);

                $disabled++;

                if ($this->logger) {
                    $this->logger->info('Abonnement désactivé pour non-paiement', [
                        'subscription_id' => $subscription['subscription_id'],
                        'invoice_id' => $subscription['invoice_id'],
                        'invoice_number' => $subscription['invoice_number'],
                        'day_of_month' => $dayOfMonth,
                    ]);
                }

                $io->writeln(sprintf(
                    '  ✓ Abonnement #%s désactivé (facture #%s non payée)',
                    $subscription['subscription_id'],
                    $subscription['invoice_number']
                ));
            } catch (\Exception $e) {
                $errorMsg = sprintf(
                    'Erreur lors de la désactivation de l\'abonnement %s: %s',
                    $subscription['subscription_id'],
                    $e->getMessage()
                );
                $errors[] = $errorMsg;

                if ($this->logger) {
                    $this->logger->error($errorMsg, [
                        'subscription_id' => $subscription['subscription_id'],
                        'exception' => $e,
                    ]);
                }

                $io->error($errorMsg);
            }
        }

        // Afficher les résultats
        if ($disabled > 0) {
            $io->success(sprintf(
                '%d abonnement(s) désactivé(s) pour non-paiement',
                $disabled
            ));
        } else {
            $io->info('Aucun abonnement à désactiver');
        }

        if (!empty($errors)) {
            $io->error(sprintf(
                '%d erreur(s) rencontrée(s)',
                count($errors)
            ));
        }

        $io->newLine();
        $io->section('Résumé');
        $io->table(
            ['Statut', 'Nombre'],
            [
                ['Abonnements désactivés', $disabled],
                ['Erreurs', count($errors)],
            ]
        );

        return Command::SUCCESS;
    }
}

