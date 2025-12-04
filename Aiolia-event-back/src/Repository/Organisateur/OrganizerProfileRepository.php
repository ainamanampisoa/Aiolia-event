<?php

namespace App\Repository\Organisateur;

use App\Entity\OrganizerProfile;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<OrganizerProfile>
 */
class OrganizerProfileRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OrganizerProfile::class);
    }

    /**
     * Récupère le profil organisateur d'un utilisateur
     */
    public function findByUser(User $user): ?OrganizerProfile
    {
        return $this->findOneBy(['utilisateur' => $user]);
    }
}

