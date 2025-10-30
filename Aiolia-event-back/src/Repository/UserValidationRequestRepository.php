<?php

namespace App\Repository;

use App\Entity\UserValidationRequest;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserValidationRequest>
 */
class UserValidationRequestRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserValidationRequest::class);
    }

    /**
     * Récupère toutes les demandes en attente
     */
    public function findPendingRequests(): array
    {
        return $this->createQueryBuilder('v')
            ->where('v.status = :status')
            ->setParameter('status', 'pending')
            ->orderBy('v.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les demandes par statut
     */
    public function findByStatus(string $status): array
    {
        return $this->createQueryBuilder('v')
            ->where('v.status = :status')
            ->setParameter('status', $status)
            ->orderBy('v.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Vérifie si un utilisateur a déjà une demande en attente
     */
    public function hasPendingRequest(int $userId): bool
    {
        $count = $this->createQueryBuilder('v')
            ->select('COUNT(v.id)')
            ->where('v.user = :userId')
            ->andWhere('v.status = :status')
            ->setParameter('userId', $userId)
            ->setParameter('status', 'pending')
            ->getQuery()
            ->getSingleScalarResult();

        return $count > 0;
    }
}

