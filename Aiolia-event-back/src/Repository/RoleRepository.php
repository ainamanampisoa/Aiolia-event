<?php

namespace App\Repository;

use App\Entity\Role;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Role>
 */
class RoleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Role::class);
    }

    public function findOneByCode(string $code): ?Role
    {
        return $this->findOneBy(['code' => strtolower(trim($code))]);
    }

    public function getByCode(string $code): Role
    {
        $role = $this->findOneByCode($code);

        if (!$role) {
            throw new \InvalidArgumentException(sprintf('Rôle "%s" introuvable', $code));
        }

        return $role;
    }
}

