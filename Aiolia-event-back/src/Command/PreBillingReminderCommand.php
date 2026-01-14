<?php

namespace App\Command;

use App\Entity\OrganizerSubscription;
use App\Entity\User;
use App\Repository\Admin\OrganizerSubscriptionRepository;
use App\Service\Organisateur\SubscriptionNotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:pre-billing-reminder',
    description: 'Envoie un rappel 7 jours avant la fin du mois pour les abonnements avec auto_renew (à exécuter entre le 24 et 31 du mois à 10h)',
)]
class PreBillingReminderCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private OrganizerSubscriptionRepository $subscriptionRepository,
        private SubscriptionNotificationService $notificationService,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Rappel pré-facturation (7 jours avant)');

        try {
            $now = new \DateTimeImmutable();
            $currentDay = (int) $now->format('d');
            $currentMonth = (int) $now->format('m');
            $currentYear = (int) $now->format('Y');

            // Vérifier si on est exactement 7 jours avant la fin du mois
            $lastDayOfMonth = (int) $now->modify('last day of this month')->format('d');
            $daysUntilEndOfMonth = $lastDayOfMonth - $currentDay;

            if ($daysUntilEndOfMonth !== 7) {
                $io->info(sprintf(
                    'Pas encore le moment (7 jours avant la fin du mois). Jours restants : %d',
                    $daysUntilEndOfMonth
                ));
                return Command::SUCCESS;
            }

            // Récupérer les abonnements actifs avec auto_renew = true
            $subscriptions = $this->findSubscriptionsWithAutoRenew();

            if (empty($subscriptions)) {
                $io->info('Aucun abonnement avec auto_renew activé trouvé.');
                return Command::SUCCESS;
            }

            $io->info(sprintf('Traitement de %d abonnement(s)...', count($subscriptions)));

            $sent = 0;
            $errors = 0;

            foreach ($subscriptions as $subscriptionData) {
                try {
                    $subscriptionId = (int) $subscriptionData['subscription_id'];
                    $userId = (int) $subscriptionData['user_id'];

                    $subscription = $this->entityManager->getRepository(OrganizerSubscription::class)->find($subscriptionId);
                    $user = $this->entityManager->getRepository(User::class)->find($userId);

                    if (!$subscription || !$user) {
                        $errors++;
                        continue;
                    }

                    // Calculer le montant du prochain paiement
                    $price = (float) $subscriptionData['price'];
                    $billingDate = new \DateTimeImmutable('first day of next month');

                    $this->notificationService->sendPreBillingReminder(
                        $user,
                        $subscription,
                        $price,
                        $subscriptionData['currency'] ?? 'MGA',
                        $billingDate
                    );

                    $sent++;
                } catch (\Exception $e) {
                    $errors++;
                    $io->error("Erreur pour l'abonnement {$subscriptionData['subscription_id']}: " . $e->getMessage());
                }
            }

            $io->success(sprintf('Rappels envoyés : %d succès, %d erreur(s)', $sent, $errors));

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Erreur lors de l\'envoi des rappels : ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    private function findSubscriptionsWithAutoRenew(): array
    {
        $connection = $this->entityManager->getConnection();

        $sql = "
            SELECT 
                os.id as subscription_id,
                op.id_utilisateur as user_id,
                sp.prix as price,
                sp.devise as currency
            FROM aiolia.abonnements_organisateurs os
            INNER JOIN aiolia.profils_organisateurs op ON op.id = os.id_profil_organisateur
            INNER JOIN aiolia.plans_abonnements sp ON sp.id = os.id_plan
            WHERE os.statut = 'active'
                AND sp.est_actif = true
                AND os.annule_le IS NULL
                AND (os.metadonnees->>'auto_renew')::boolean = true
        ";

        return $connection->fetchAllAssociative($sql);
    }
}

