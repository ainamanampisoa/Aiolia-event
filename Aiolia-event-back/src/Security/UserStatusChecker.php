<?php

namespace App\Security;

use App\Entity\User;
use App\Enum\Role as UserRoleEnum;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class UserStatusChecker implements UserCheckerInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function checkPreAuth(UserInterface $user): void
    {
        if (!$user instanceof User) {
            return;
        }

        $this->assertAccountIsActive($user);
        $this->assertRoleIsAllowed($user);
        $this->assertSubscriptionIsActive($user);
    }

    public function checkPostAuth(UserInterface $user): void
    {
        if (!$user instanceof User) {
            return;
        }

        $this->assertAccountIsActive($user);
        $this->assertRoleIsAllowed($user);
        $this->assertSubscriptionIsActive($user);
    }

    private function assertAccountIsActive(User $user): void
    {
        if ($user->getAccountStatus() !== 'active') {
            throw new CustomUserMessageAuthenticationException(
                'Votre compte doit être validé par un administrateur avant de pouvoir vous connecter.'
            );
        }
    }

    private function assertRoleIsAllowed(User $user): void
    {
        if (!in_array($user->getRole(), [UserRoleEnum::ADMIN, UserRoleEnum::ORGANIZER], true)) {
            throw new CustomUserMessageAuthenticationException(
                'Seuls les comptes Administrateur ou Organisateur peuvent accéder à Aiolia Event Back.'
            );
        }
    }

    /**
     * Vérifie que l'abonnement de l'organisateur est actif
     * Bloque la connexion si la facture du mois courant n'est pas payée
     * Règle : pas de paiement du mois = pas de connexion pour ce mois
     */
    private function assertSubscriptionIsActive(User $user): void
    {
        // Vérifier uniquement pour les organisateurs
        if ($user->getRole() !== UserRoleEnum::ORGANIZER) {
            return;
        }

        $connection = $this->entityManager->getConnection();
        
        // Vérifier le statut de l'abonnement de l'organisateur
        $subscriptionSql = "
            SELECT os.status, os.id
            FROM aiolia.organizer_subscriptions os
            INNER JOIN aiolia.organizer_profiles op ON op.id = os.organizer_profile_id
            WHERE op.user_id = :user_id
            ORDER BY os.created_at DESC
            LIMIT 1
        ";
        
        $subscriptionData = $connection->fetchAssociative($subscriptionSql, ['user_id' => $user->getId()]);
        
        // Si l'organisateur n'a pas d'abonnement, autoriser la connexion (pour qu'il puisse s'abonner)
        if ($subscriptionData === false || $subscriptionData === null) {
            return;
        }
        
        $subscriptionStatus = $subscriptionData['status'] ?? null;
        $subscriptionId = $subscriptionData['id'] ?? null;
        
        // Si l'abonnement est suspendu, vérifier la facture du mois courant
        if ($subscriptionStatus === 'suspended' && $subscriptionId) {
            $this->checkCurrentMonthInvoice($user, $subscriptionId);
            return;
        }
        
        // Si l'abonnement est annulé ou expiré, bloquer la connexion
        if (in_array($subscriptionStatus, ['cancelled', 'expired'], true)) {
            $message = match($subscriptionStatus) {
                'cancelled' => 'Votre abonnement a été annulé. Veuillez contacter l\'administration pour réactiver votre compte.',
                'expired' => 'Votre abonnement a expiré. Veuillez renouveler votre abonnement pour continuer à utiliser la plateforme.',
                default => 'Votre abonnement n\'est pas actif. Veuillez contacter l\'administration.'
            };
            
            throw new CustomUserMessageAuthenticationException($message);
        }
    }

    /**
     * Vérifie la facture du mois courant
     * Règle : si la facture du mois courant n'est pas payée, bloquer la connexion pour ce mois
     */
    private function checkCurrentMonthInvoice(User $user, int $subscriptionId): void
    {
        $connection = $this->entityManager->getConnection();
        $now = new \DateTimeImmutable();
        $currentMonth = (int) $now->format('n');
        $currentYear = (int) $now->format('Y');
        
        // Récupérer la facture du mois courant
        $currentInvoiceSql = "
            SELECT 
                si.id,
                si.invoice_number,
                si.issued_at,
                si.status,
                si.paid_at
            FROM aiolia.subscription_invoices si
            WHERE si.subscription_id = :subscription_id
                AND EXTRACT(YEAR FROM si.issued_at) = :current_year
                AND EXTRACT(MONTH FROM si.issued_at) = :current_month
            ORDER BY si.issued_at DESC
            LIMIT 1
        ";
        
        $currentInvoice = $connection->fetchAssociative($currentInvoiceSql, [
            'subscription_id' => $subscriptionId,
            'current_year' => $currentYear,
            'current_month' => $currentMonth,
        ]);
        
        // Si pas de facture pour ce mois, autoriser la connexion (la facture sera créée plus tard)
        if ($currentInvoice === false || $currentInvoice === null) {
            return;
        }
        
        // Si la facture du mois courant est payée, autoriser la connexion
        if ($currentInvoice['paid_at'] !== null) {
            return;
        }
        
        // Si la facture du mois courant n'est pas payée, bloquer la connexion
        $months = [
            1 => 'janvier', 2 => 'février', 3 => 'mars', 4 => 'avril',
            5 => 'mai', 6 => 'juin', 7 => 'juillet', 8 => 'août',
            9 => 'septembre', 10 => 'octobre', 11 => 'novembre', 12 => 'décembre'
        ];
        
        $monthName = $months[$currentMonth] ?? $currentMonth;
        $invoiceStatus = $currentInvoice['status'];
        
        // Message expliquant qu'il doit payer la facture du mois courant
        $message = sprintf(
            'Vous ne pouvez pas vous connecter ce mois (%s %d) car votre facture d\'abonnement du mois n\'est pas payée. ' .
            'Pour accéder à votre compte ce mois-ci, vous devez régler la facture d\'abonnement de %s %d. ' .
            'Vous pouvez faire une pause et vous reconnecter plus tard, mais pour chaque mois où vous souhaitez vous connecter, vous devez avoir payé la facture de ce mois. ' .
            'Vous pouvez régler votre abonnement dans les 5 derniers jours du mois précédent ou dans les 5 premiers jours du mois courant. ' .
            'Exemple : Si vous souhaitez vous connecter en décembre, vous devez avoir payé la facture de décembre (dans les 5 derniers jours de novembre ou les 5 premiers jours de décembre). ' .
            'Veuillez contacter l\'administration pour régulariser votre situation.',
            $monthName,
            $currentYear,
            $monthName,
            $currentYear
        );
        
        throw new CustomUserMessageAuthenticationException($message);
    }
}

