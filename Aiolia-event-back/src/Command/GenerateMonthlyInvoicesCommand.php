<?php

namespace App\Command;

use App\Service\SubscriptionInvoiceGenerationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:generate-monthly-invoices',
    description: 'Génère automatiquement les factures mensuelles pour les organisateurs (à exécuter les 5 derniers jours du mois)',
)]
class GenerateMonthlyInvoicesCommand extends Command
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
        $daysInMonth = (int) $today->format('t'); // Nombre de jours dans le mois

        // Vérifier si on est dans les 5 derniers jours du mois
        if ($dayOfMonth < ($daysInMonth - 4)) {
            $io->warning(sprintf(
                'Cette commande doit être exécutée pendant les 5 derniers jours du mois (du %d au %d). Aujourd\'hui est le jour %d.',
                $daysInMonth - 4,
                $daysInMonth,
                $dayOfMonth
            ));
            
            if (!$io->confirm('Voulez-vous quand même continuer ?', false)) {
                return Command::SUCCESS;
            }
        }

        $io->title('Génération des factures mensuelles');

        // Le mois suivant
        $nextMonth = $today->modify('first day of next month');
        $io->info(sprintf(
            'Génération des factures pour le mois suivant: %s %d',
            $this->getMonthName((int) $nextMonth->format('m')),
            (int) $nextMonth->format('Y')
        ));

        $results = $this->invoiceGenerationService->generateMonthlyInvoices($nextMonth);

        // Afficher les résultats
        if ($results['created'] > 0) {
            $io->success(sprintf(
                '%d facture(s) créée(s) avec succès',
                $results['created']
            ));
        }

        if ($results['skipped'] > 0) {
            $io->note(sprintf(
                '%d facture(s) déjà existante(s) pour ce mois',
                $results['skipped']
            ));
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
                ['Créées', $results['created']],
                ['Ignorées (déjà existantes)', $results['skipped']],
                ['Erreurs', count($results['errors'])],
            ]
        );

        return Command::SUCCESS;
    }

    private function getMonthName(int $month): string
    {
        $months = [
            1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril',
            5 => 'Mai', 6 => 'Juin', 7 => 'Juillet', 8 => 'Août',
            9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre',
        ];

        return $months[$month] ?? '';
    }
}

