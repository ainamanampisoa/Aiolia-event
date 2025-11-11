<?php

namespace App\Repository;

use App\Entity\User;
use App\Entity\UserRoleAssignment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserRoleAssignment>
 */
class UserRoleAssignmentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserRoleAssignment::class);
    }

    /**
     * @return list<string>
     */
    public function findRoleCodesForUser(User $user): array
    {
        $rows = $this->createQueryBuilder('a')
            ->select('DISTINCT role.code AS code')
            ->join('a.role', 'role')
            ->andWhere('a.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getArrayResult();

        return array_map(static fn (array $row): string => strtoupper((string) $row['code']), $rows);
    }
}
