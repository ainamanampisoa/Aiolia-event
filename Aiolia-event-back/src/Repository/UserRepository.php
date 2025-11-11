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
                ->orderBy('u.createdAt', 'DESC')
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

        [$firstName, $lastName] = $parts + ['', ''];

        $user = $this->createQueryBuilder('u')
            ->where('LOWER(u.firstName) = :first')
            ->andWhere('LOWER(COALESCE(u.lastName, \'\')) = :last')
            ->setParameter('first', mb_strtolower($firstName))
            ->setParameter('last', mb_strtolower($lastName))
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

        $user->setPasswordHash($newHashedPassword);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    /**
     * Retourne la liste des comptes en attente de validation.
     */
    public function findAccountsPendingValidation(): array
    {
        return $this->createQueryBuilder('u')
            ->where('u.status = :status')
            ->setParameter('status', User::STATUS_PENDING)
            ->orderBy('u.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }
}

