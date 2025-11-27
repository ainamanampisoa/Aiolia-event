<?php

namespace App\Command;

use App\Service\Admin\SubscriptionInvoiceCleanupService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:cleanup-overdue-invoices',
    description: 'Nettoie les factures existantes en "overdue" depuis plus de 10 jours - les annule (void) et met en pause les abonnements',
)]
class CleanupOverdueInvoicesCommand extends Command
{
    public function __construct(
        private SubscriptionInvoiceCleanupService $cleanupService,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Nettoyage des factures en retard');

        $io->note('Cette commande annule toutes les factures en statut "overdue" depuis plus de 10 jours.');
        $io->note('Les factures seront mises en statut "void" et les abonnements associés seront mis en pause.');

        if (!$io->confirm('Voulez-vous continuer ?', true)) {
            return Command::SUCCESS;
        }

        try {
            $results = $this->cleanupService->cleanupOverdueInvoices();

            if ($results['voided'] > 0) {
                $io->success(sprintf(
                    '%d facture(s) annulée(s) et %d abonnement(s) mis en pause',
                    $results['voided'],
                    $results['paused']
                ));

                // Afficher les détails
                if (!empty($results['details'])) {
                    $io->section('Détails des factures annulées');
                    $tableData = [];
                    foreach ($results['details'] as $detail) {
                        $tableData[] = [
                            $detail['invoice_number'],
                            $detail['old_status'],
                            $detail['new_status'],
                            $detail['subscription_id'] ?? 'N/A',
                            $detail['subscription_new_status'] ?? 'N/A',
                            $detail['days_overdue'] . ' jours',
                        ];
                    }

                    $io->table(
                        ['N° Facture', 'Ancien statut', 'Nouveau statut', 'ID Abonnement', 'Statut abonnement', 'Jours de retard'],
                        $tableData
                    );
                }
            } else {
                $io->info('Aucune facture en retard à nettoyer (toutes les factures sont à jour)');
            }

            if (!empty($results['errors'])) {
                $io->warning(sprintf(
                    '%d erreur(s) rencontrée(s)',
                    count($results['errors'])
                ));
                
                foreach ($results['errors'] as $error) {
                    $io->writeln(sprintf('  - %s', $error));
                }
            }

            $io->newLine();
            $io->section('Résumé');
            $io->table(
                ['Statut', 'Nombre'],
                [
                    ['Factures annulées', $results['voided']],
                    ['Abonnements mis en pause', $results['paused']],
                    ['Erreurs', count($results['errors'])],
                ]
            );

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error(sprintf(
                'Erreur lors du nettoyage des factures en retard : %s',
                $e->getMessage()
            ));
            
            return Command::FAILURE;
        }
    }
}

