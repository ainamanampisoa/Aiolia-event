<?php

namespace App\Command;

use App\Service\SubscriptionInvoiceGenerationService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:mark-overdue-invoices',
    description: 'Marque les factures non payées comme en retard (à exécuter à partir du 10ème jour du mois - date limite de paiement)',
)]
class MarkOverdueInvoicesCommand extends Command
{
    public function __construct(
        private SubscriptionInvoiceGenerationService $invoiceGenerationService,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $today = new \DateTimeImmutable();
        $dayOfMonth = (int) $today->format('d');

        // Vérifier si on est après le 10ème jour du mois (date limite de paiement)
        if ($dayOfMonth < 10) {
            $io->warning(sprintf(
                'Cette commande doit être exécutée à partir du 10ème jour du mois (date limite de paiement). Aujourd\'hui est le jour %d.',
                $dayOfMonth
            ));
            
            if (!$io->confirm('Voulez-vous quand même continuer ?', false)) {
                return Command::SUCCESS;
            }
        }

        $io->title('Marquage des factures en retard');

        $results = $this->invoiceGenerationService->markOverdueInvoices($today);

        // Afficher les résultats
        if ($results['updated'] > 0) {
            $io->success(sprintf(
                '%d facture(s) marquée(s) comme en retard',
                $results['updated']
            ));
        } else {
            $io->info('Aucune facture à marquer comme en retard');
        }

        if (!empty($results['errors'])) {
            $io->error(sprintf(
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
                ['Factures mises en retard', $results['updated']],
                ['Erreurs', count($results['errors'])],
            ]
        );

        return Command::SUCCESS;
    }
}

