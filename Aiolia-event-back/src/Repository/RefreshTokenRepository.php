<?php

namespace App\Repository;

use App\Entity\RefreshToken;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<RefreshToken>
 */
class RefreshTokenRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RefreshToken::class);
    }

    public function findOneValidByHash(string $tokenHash): ?RefreshToken
    {
        $qb = $this->createQueryBuilder('rt')
            ->where('rt.tokenHash = :hash')
            ->andWhere('rt.revokedAt IS NULL')
            ->andWhere('rt.replacedByToken IS NULL')
            ->andWhere('rt.expiresAt > :now')
            ->setParameter('hash', $tokenHash)
            ->setParameter('now', new \DateTimeImmutable())
            ->setMaxResults(1);

        try {
            return $qb->getQuery()->getOneOrNullResult();
        } catch (NonUniqueResultException) {
            return null;
        }
    }

    /**
     * Révoque tous les refresh tokens actifs pour un utilisateur donné.
     */
    public function revokeAllForUser(User $user, ?string $sessionId = null): int
    {
        $qb = $this->createQueryBuilder('rt')
            ->update()
            ->set('rt.revokedAt', ':now')
            ->where('rt.user = :user')
            ->andWhere('rt.revokedAt IS NULL')
            ->setParameter('now', new \DateTimeImmutable())
            ->setParameter('user', $user);

        if ($sessionId !== null) {
            $qb->andWhere('rt.sessionId = :sessionId')
                ->setParameter('sessionId', $sessionId);
        }

        return $qb->getQuery()->execute();
    }
}


