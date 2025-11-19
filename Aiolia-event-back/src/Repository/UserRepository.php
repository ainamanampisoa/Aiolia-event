<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;
use Symfony\Bridge\Doctrine\Security\User\UserLoaderInterface;
use Doctrine\DBAL\ArrayParameterType;
use function mb_strtolower;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface, UserLoaderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function loadUserByIdentifier(string $identifier): User
    {
        $identifier = trim($identifier);

        if ($identifier === '') {
            throw new UserNotFoundException('Identifiant vide. Merci de saisir votre prénom et votre nom.');
        }

        if (str_contains($identifier, '@')) {
            $user = $this->createQueryBuilder('u')
                ->where('LOWER(u.email) = :email')
                ->setParameter('email', mb_strtolower($identifier))
                ->orderBy('u.creeLe', 'DESC')
                ->setMaxResults(1)
                ->getQuery()
                ->getOneOrNullResult();

            if (!$user instanceof User) {
                throw new UserNotFoundException(sprintf('Aucun utilisateur ne correspond à l\'email "%s".', $identifier));
            }

            return $user;
        }

        $parts = preg_split('/\s+/', $identifier, 2, \PREG_SPLIT_NO_EMPTY);

        if (!$parts || count($parts) < 2) {
            throw new UserNotFoundException('Veuillez saisir votre prénom suivi de votre nom.');
        }

        [$prenom, $nom] = $parts + ['', ''];

        $user = $this->createQueryBuilder('u')
            ->where('LOWER(u.prenom) = :prenom')
            ->andWhere('LOWER(COALESCE(u.nom, \'\')) = :nom')
            ->setParameter('prenom', mb_strtolower($prenom))
            ->setParameter('nom', mb_strtolower($nom))
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if (!$user instanceof User) {
            throw new UserNotFoundException(sprintf('Aucun utilisateur ne correspond à "%s".', $identifier));
        }

        return $user;
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $user->setHashMotDePasse($newHashedPassword);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    /**
     * Retourne la liste des comptes en attente de validation.
     */
    public function findAccountsPendingValidation(): array
    {
        return $this->createQueryBuilder('u')
            ->where('u.statut = :statut')
            ->setParameter('statut', User::STATUS_PENDING)
            ->orderBy('u.creeLe', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Retourne le statut d'abonnement (pending/active/paused) pour les organisateurs fournis.
     *
     * @param list<int|string> $userIds
     * @return array<int|string, string>
     */
    public function getOrganizerSubscriptionStatuses(array $userIds): array
    {
        if (empty($userIds)) {
            return [];
        }

        $connection = $this->getEntityManager()->getConnection();

        $sql = "
            SELECT DISTINCT ON (po.id_utilisateur)
                po.id_utilisateur AS user_id,
                os.statut
            FROM aiolia.abonnements_organisateurs os
            INNER JOIN aiolia.profils_organisateurs po ON po.id = os.id_profil_organisateur
            WHERE po.id_utilisateur IN (:userIds)
                AND os.annule_le IS NULL
            ORDER BY po.id_utilisateur, os.cree_le DESC
        ";

        $results = $connection->executeQuery(
            $sql,
            ['userIds' => $userIds],
            ['userIds' => ArrayParameterType::INTEGER]
        )->fetchAllAssociative();

        $statuses = [];
        foreach ($results as $row) {
            $statuses[$row['user_id']] = $row['statut'];
        }

        return $statuses;
    }
}

