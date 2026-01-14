<?php

namespace App\Command;

use App\Service\Organisateur\MonthlyInvoiceGenerationService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:generate-monthly-invoices',
    description: 'Génère automatiquement les factures mensuelles pour tous les abonnements actifs (à exécuter le 1er de chaque mois à 1h)',
)]
class GenerateMonthlyInvoicesCommand extends Command
{
    public function __construct(
        private MonthlyInvoiceGenerationService $invoiceGenerationService,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Génération des factures mensuelles');

        try {
            $results = $this->invoiceGenerationService->generateMonthlyInvoices();

            $io->success(sprintf(
                'Génération terminée : %d facture(s) créée(s), %d facture(s) déjà existante(s)',
                $results['created'],
                $results['skipped']
            ));

            if (!empty($results['errors'])) {
                $io->warning(sprintf('%d erreur(s) rencontrée(s)', count($results['errors'])));
                foreach ($results['errors'] as $error) {
                    $io->writeln("  - $error");
                }
            }

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Erreur lors de la génération des factures : ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}

