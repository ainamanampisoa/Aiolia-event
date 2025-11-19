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
        if ($user->getStatutCompte() !== 'active') {
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
            SELECT os.statut, os.id
            FROM aiolia.abonnements_organisateurs os
            INNER JOIN aiolia.profils_organisateurs op ON op.id = os.id_profil_organisateur
            WHERE op.id_utilisateur = :user_id
            ORDER BY os.cree_le DESC
            LIMIT 1
        ";
        
        $subscriptionData = $connection->fetchAssociative($subscriptionSql, ['user_id' => $user->getId()]);
        
        // Si l'organisateur n'a pas d'abonnement, autoriser la connexion (pour qu'il puisse s'abonner)
        if ($subscriptionData === false || $subscriptionData === null) {
            return;
        }
        
        $subscriptionStatus = $subscriptionData['statut'] ?? null;
        $subscriptionId = $subscriptionData['id'] ?? null;
        
        // Si l'abonnement est en pause, autoriser la connexion (avec accès limités)
        // Les règles d'accès pour les organisateurs en pause sont gérées dans le front-end
        if ($subscriptionStatus === 'paused') {
            // Les organisateurs en pause peuvent se connecter mais avec des accès limités
            // Cette logique sera implémentée dans le module organisateur (front-end)
            return;
        }
        
        // Si l'abonnement est en attente (pending), autoriser la connexion pour qu'il puisse s'abonner
        if ($subscriptionStatus === 'pending') {
            return;
        }
        
        // Si l'abonnement n'est pas actif, bloquer la connexion
        if ($subscriptionStatus !== 'active') {
            throw new CustomUserMessageAuthenticationException(
                'Votre abonnement n\'est pas actif. Veuillez contacter l\'administration pour réactiver votre compte.'
            );
        }
    }

}

