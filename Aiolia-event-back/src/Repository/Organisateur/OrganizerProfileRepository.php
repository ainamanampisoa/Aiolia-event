<?php

namespace App\Repository\Organisateur;

use App\Entity\OrganizerProfile;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;


class OrganizerProfileRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OrganizerProfile::class);
    }

    
    public function findByUser(User $user): ?OrganizerProfile
    {
        return $this->findOneBy(['utilisateur' => $user]);
    }
}

