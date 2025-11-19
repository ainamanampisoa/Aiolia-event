<?php

namespace App\Command;

use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:auto-pause-unpaid-subscriptions',
    description: 'Met automatiquement en pause les abonnements non payés après le 10ème jour du mois (à exécuter le 11ème jour)',
)]
class AutoPauseUnpaidSubscriptionsCommand extends Command
{
    public function __construct(
        private Connection $connection,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $today = new \DateTimeImmutable();
        $dayOfMonth = (int) $today->format('d');

        // Vérifier si on est après le 10ème jour du mois
        if ($dayOfMonth <= 10) {
            $io->warning(sprintf(
                'Cette commande doit être exécutée après le 10ème jour du mois (date limite de paiement). Aujourd\'hui est le jour %d.',
                $dayOfMonth
            ));
            
            if (!$io->confirm('Voulez-vous quand même continuer ?', false)) {
                return Command::SUCCESS;
            }
        }

        $io->title('Mise en pause automatique des abonnements non payés');

        try {
            // Exécuter la fonction SQL
            $sql = 'SELECT * FROM aiolia.auto_pause_unpaid_subscriptions()';
            $results = $this->connection->fetchAllAssociative($sql);

            $pausedCount = count($results);

            if ($pausedCount > 0) {
                $io->success(sprintf(
                    '%d abonnement(s) mis en pause automatiquement',
                    $pausedCount
                ));

                // Afficher les détails
                $io->section('Détails des abonnements mis en pause');
                $tableData = [];
                foreach ($results as $result) {
                    $tableData[] = [
                        $result['subscription_id'],
                        $result['organizer_user_id'],
                        $result['invoice_number'],
                        $result['old_status'],
                        $result['new_status'],
                        (new \DateTimeImmutable($result['paused_at']))->format('d/m/Y H:i:s'),
                    ];
                }

                $io->table(
                    ['ID Abonnement', 'ID Organisateur', 'N° Facture', 'Ancien statut', 'Nouveau statut', 'Mis en pause le'],
                    $tableData
                );
            } else {
                $io->info('Aucun abonnement à mettre en pause (tous les abonnements sont à jour)');
            }

            $io->newLine();
            $io->section('Résumé');
            $io->table(
                ['Statut', 'Nombre'],
                [
                    ['Abonnements mis en pause', $pausedCount],
                ]
            );

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error(sprintf(
                'Erreur lors de la mise en pause automatique : %s',
                $e->getMessage()
            ));
            
            return Command::FAILURE;
        }
    }
}

