<?php

namespace App\Repository\Organisateur;

use App\Entity\Event;
use App\Entity\User;
use App\Entity\EventCategory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;


class EventRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Event::class);
    }

    
    public function getAll(): array
    {
        return $this->createQueryBuilder('e')
            ->orderBy('e.creeLe', 'DESC')
            ->getQuery()
            ->getResult();
    }

    
    public function getById(string $id): ?Event
    {
        return $this->find($id);
    }

    
    public function create(Event $event): Event
    {
        $this->getEntityManager()->persist($event);
        $this->getEntityManager()->flush();

        return $event;
    }

    
    public function update(Event $event): Event
    {
        $this->getEntityManager()->flush();

        return $event;
    }

    
    public function delete(Event $event): void
    {
        $this->getEntityManager()->remove($event);
        $this->getEntityManager()->flush();
    }

    
    public function findUpcomingEvents(int $limit = 0): array
    {
        $qb = $this->createQueryBuilder('e')
            ->andWhere('e.status = :status')
            ->andWhere('e.startDate > :now')
            ->setParameter('status', 'published')
            ->setParameter('now', new \DateTime())
            ->orderBy('e.startDate', 'ASC');

        if ($limit) {
            $qb->setMaxResults($limit);
        }

        return $qb->getQuery()->getResult();
    }

    
    public function findFeaturedEvents(int $limit = 6): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.status = :status')
            ->andWhere('e.isFeatured = :featured')
            ->andWhere('e.startDate > :now')
            ->setParameter('status', 'published')
            ->setParameter('featured', true)
            ->setParameter('now', new \DateTime())
            ->orderBy('e.startDate', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    
    public function searchEvents(string $query, array $filters = []): array
    {
        $qb = $this->createQueryBuilder('e')
            ->leftJoin('e.category', 'c')
            ->andWhere('e.status = :status')
            ->setParameter('status', 'published');

        
        if (!empty($query)) {
            $qb->andWhere('e.title LIKE :query OR e.description LIKE :query OR e.location LIKE :query')
                ->setParameter('query', '%' . $query . '%');
        }

        
        if (!empty($filters['category'])) {
            $qb->andWhere('c.id = :category')
                ->setParameter('category', $filters['category']);
        }

        
        if (!empty($filters['startDate'])) {
            $qb->andWhere('e.startDate >= :startDate')
                ->setParameter('startDate', $filters['startDate']);
        }

        if (!empty($filters['endDate'])) {
            $qb->andWhere('e.endDate <= :endDate')
                ->setParameter('endDate', $filters['endDate']);
        }

        
        if (!empty($filters['location'])) {
            $qb->andWhere('e.location LIKE :location')
                ->setParameter('location', '%' . $filters['location'] . '%');
        }

        return $qb->orderBy('e.startDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    
    public function findByOrganizer(User $organizer, ?string $status = null, ?int $limit = null): array
    {
        $qb = $this->createQueryBuilder('e')
            ->leftJoin('e.profilOrganisateur', 'op')
            ->leftJoin('e.organisateursEvenements', 'oe')
            ->leftJoin('oe.profilOrganisateur', 'op2')
            ->leftJoin('op.utilisateur', 'u1')
            ->leftJoin('op2.utilisateur', 'u2')
            ->where('u1.id = :userId OR u2.id = :userId')
            ->setParameter('userId', $organizer->getId())
            ->groupBy('e.id')
            ->orderBy('e.creeLe', 'DESC');

        if ($status !== null) {
            $qb->andWhere('e.statut = :status')
                ->setParameter('status', $status);
        }

        if ($limit !== null) {
            $qb->setMaxResults($limit);
        }

        return $qb->getQuery()->getResult();
    }

    
    public function countByOrganizer(User $organizer, ?string $status = null): int
    {
        $qb = $this->createQueryBuilder('e')
            ->select('COUNT(DISTINCT e.id)')
            ->leftJoin('e.profilOrganisateur', 'op')
            ->leftJoin('e.organisateursEvenements', 'oe')
            ->leftJoin('oe.profilOrganisateur', 'op2')
            ->leftJoin('op.utilisateur', 'u1')
            ->leftJoin('op2.utilisateur', 'u2')
            ->where('u1.id = :userId OR u2.id = :userId')
            ->setParameter('userId', $organizer->getId());

        if ($status !== null) {
            $qb->andWhere('e.statut = :status')
                ->setParameter('status', $status);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    
    public function findByCategory(EventCategory $category, int $limit = 0): array
    {
        $qb = $this->createQueryBuilder('e')
            ->andWhere('e.category = :category')
            ->andWhere('e.status = :status')
            ->andWhere('e.startDate > :now')
            ->setParameter('category', $category)
            ->setParameter('status', 'published')
            ->setParameter('now', new \DateTime())
            ->orderBy('e.startDate', 'ASC');

        if ($limit) {
            $qb->setMaxResults($limit);
        }

        return $qb->getQuery()->getResult();
    }

    
    public function countByStatus(string $status): int
    {
        return $this->createQueryBuilder('e')
            ->select('COUNT(e.id)')
            ->andWhere('e.status = :status')
            ->setParameter('status', $status)
            ->getQuery()
            ->getSingleScalarResult();
    }

    
    public function getEventStatistics(Event $event): array
    {
        
        return [
            'tickets_sold' => 0,
            'revenue' => 0,
            'views' => 0,
        ];
    }

    
    public function searchMultiCriteria(
        string $idOrganisateur,
        ?string $nomLieu = null,
        ?\DateTimeInterface $dateDebut = null,
        ?\DateTimeInterface $dateFin = null,
        ?string $typeEvenementId = null,
        ?float $prixMin = null,
        ?float $prixMax = null,
        ?string $triPrix = null,
        ?int $limit = null,
        ?int $offset = null,
        ?string $statut = null
    ): array {
        $qb = $this->createQueryBuilder('e')
            ->leftJoin('e.lieu', 'lieu')
            ->leftJoin('e.typeEvenement', 'type')
            ->leftJoin('e.profilOrganisateur', 'profil')
            ->leftJoin('e.organisateursEvenements', 'oe')
            ->leftJoin('oe.profilOrganisateur', 'profil2')
            ->leftJoin('App\Entity\TypeBillet', 'tb', 'WITH', 'tb.evenement = e')
            ->andWhere('profil.id = :idOrganisateur OR profil2.id = :idOrganisateur')
            ->setParameter('idOrganisateur', $idOrganisateur)
            ->groupBy('e.id');
        $now = new \DateTime('now', new \DateTimeZone('Indian/Antananarivo'));

        
        if ($nomLieu !== null && $nomLieu !== '') {
            
            if (is_numeric($nomLieu)) {
                $qb->andWhere('lieu.id = :lieuId')
                    ->setParameter('lieuId', $nomLieu);
            } else {
                $qb->andWhere('LOWER(lieu.nom) LIKE LOWER(:nomLieu)')
                    ->setParameter('nomLieu', '%' . $nomLieu . '%');
            }
        }

        
        // Filtre par période : inclut tous les événements qui chevauchent la période
        // Un événement chevauche la période si :
        // - Il commence avant ou pendant la période ET
        // - Il se termine après ou pendant la période (ou n'a pas de date de fin)
        // Logique : (commenceLe <= dateFin) AND (seTermineLe IS NULL OR seTermineLe >= dateDebut)
        // Note: Les dates sont déjà normalisées dans le contrôleur (dateFrom à 00:00:00, dateTo à 23:59:59)
        if ($dateDebut !== null && $dateFin !== null) {
            $qb->andWhere('e.commenceLe <= :dateFin')
                ->andWhere('(e.seTermineLe IS NULL OR e.seTermineLe >= :dateDebut)')
                ->setParameter('dateDebut', $dateDebut)
                ->setParameter('dateFin', $dateFin);
        } elseif ($dateDebut !== null) {
            // Si seule la date de début est fournie : événements qui commencent après ou à cette date
            $qb->andWhere('e.commenceLe >= :dateDebut')
                ->setParameter('dateDebut', $dateDebut);
        } elseif ($dateFin !== null) {
            // Si seule la date de fin est fournie : événements qui se terminent avant ou à cette date
            $qb->andWhere('(e.seTermineLe IS NULL OR e.seTermineLe <= :dateFin)')
                ->setParameter('dateFin', $dateFin);
        }

        
        if ($typeEvenementId !== null && $typeEvenementId !== '0') {
            $qb->andWhere('type.id = :typeEvenementId')
                ->setParameter('typeEvenementId', $typeEvenementId);
        }

        
        if ($prixMin !== null && $prixMin > 0) {
            $qb->andHaving('MIN(tb.prixDeBase) >= :prixMin')
                ->setParameter('prixMin', $prixMin);
        }

        
        if ($prixMax !== null && $prixMax > 0) {
            $qb->andHaving('MIN(tb.prixDeBase) <= :prixMax')
                ->setParameter('prixMax', $prixMax);
        }

        
        if ($statut === 'live') {
            $qb->andWhere('e.statut = :statutLive')
                ->andWhere('e.commenceLe <= :nowLive')
                ->andWhere('(e.seTermineLe IS NULL OR e.seTermineLe >= :nowLive)')
                ->setParameter('statutLive', Event::STATUS_PUBLISHED)
                ->setParameter('nowLive', $now);
        } elseif ($statut === 'upcoming') {
            $qb->andWhere('e.statut = :statutUpcoming')
                ->andWhere('e.commenceLe > :nowUpcoming')
                ->setParameter('statutUpcoming', Event::STATUS_PUBLISHED)
                ->setParameter('nowUpcoming', $now);
        } elseif ($statut !== null && $statut !== '') {
            $qb->andWhere('e.statut = :statut')
                ->setParameter('statut', $statut);
        }

        
        if ($triPrix === 'asc') {
            $qb->orderBy('MIN(tb.prixDeBase)', 'ASC');
        } elseif ($triPrix === 'desc') {
            $qb->orderBy('MIN(tb.prixDeBase)', 'DESC');
        } else {
            
            $qb->orderBy('e.commenceLe', 'ASC');
        }

        
        if ($limit !== null && $limit > 0) {
            $qb->setMaxResults($limit);
        }

        if ($offset !== null && $offset > 0) {
            $qb->setFirstResult($offset);
        }

        return $qb->getQuery()->getResult();
    }

    
    public function countSearchMultiCriteria(
        string $idOrganisateur,
        ?string $nomLieu = null,
        ?\DateTimeInterface $dateDebut = null,
        ?\DateTimeInterface $dateFin = null,
        ?string $typeEvenementId = null,
        ?float $prixMin = null,
        ?float $prixMax = null,
        ?string $statut = null
    ): int {
        $qb = $this->createQueryBuilder('e')
            ->select('COUNT(DISTINCT e.id)')
            ->leftJoin('e.lieu', 'lieu')
            ->leftJoin('e.typeEvenement', 'type')
            ->leftJoin('e.profilOrganisateur', 'profil')
            ->leftJoin('e.organisateursEvenements', 'oe')
            ->leftJoin('oe.profilOrganisateur', 'profil2')
            ->leftJoin('App\Entity\TypeBillet', 'tb', 'WITH', 'tb.evenement = e')
            ->andWhere('profil.id = :idOrganisateur OR profil2.id = :idOrganisateur')
            ->setParameter('idOrganisateur', $idOrganisateur);
        $now = new \DateTime('now', new \DateTimeZone('Indian/Antananarivo'));

        
        if ($nomLieu !== null && $nomLieu !== '') {
            
            if (is_numeric($nomLieu)) {
                $qb->andWhere('lieu.id = :lieuId')
                    ->setParameter('lieuId', $nomLieu);
            } else {
                $qb->andWhere('LOWER(lieu.nom) LIKE LOWER(:nomLieu)')
                    ->setParameter('nomLieu', '%' . $nomLieu . '%');
            }
        }

        
        // Filtre par période : inclut tous les événements qui chevauchent la période
        // Un événement chevauche la période si :
        // - Il commence avant ou pendant la période ET
        // - Il se termine après ou pendant la période (ou n'a pas de date de fin)
        // Logique : (commenceLe <= dateFin) AND (seTermineLe IS NULL OR seTermineLe >= dateDebut)
        // Note: Les dates sont déjà normalisées dans le contrôleur (dateFrom à 00:00:00, dateTo à 23:59:59)
        if ($dateDebut !== null && $dateFin !== null) {
            $qb->andWhere('e.commenceLe <= :dateFin')
                ->andWhere('(e.seTermineLe IS NULL OR e.seTermineLe >= :dateDebut)')
                ->setParameter('dateDebut', $dateDebut)
                ->setParameter('dateFin', $dateFin);
        } elseif ($dateDebut !== null) {
            // Si seule la date de début est fournie : événements qui commencent après ou à cette date
            $qb->andWhere('e.commenceLe >= :dateDebut')
                ->setParameter('dateDebut', $dateDebut);
        } elseif ($dateFin !== null) {
            // Si seule la date de fin est fournie : événements qui se terminent avant ou à cette date
            $qb->andWhere('(e.seTermineLe IS NULL OR e.seTermineLe <= :dateFin)')
                ->setParameter('dateFin', $dateFin);
        }

        
        if ($typeEvenementId !== null && $typeEvenementId !== '0') {
            $qb->andWhere('type.id = :typeEvenementId')
                ->setParameter('typeEvenementId', $typeEvenementId);
        }

        
        if ($prixMin !== null && $prixMin > 0) {
            $qb->andHaving('MIN(tb.prixDeBase) >= :prixMin')
                ->setParameter('prixMin', $prixMin);
        }

        
        if ($prixMax !== null && $prixMax > 0) {
            $qb->andHaving('MIN(tb.prixDeBase) <= :prixMax')
                ->setParameter('prixMax', $prixMax);
        }

        
        if ($statut === 'live') {
            $qb->andWhere('e.statut = :statutLive')
                ->andWhere('e.commenceLe <= :nowLive')
                ->andWhere('(e.seTermineLe IS NULL OR e.seTermineLe >= :nowLive)')
                ->setParameter('statutLive', Event::STATUS_PUBLISHED)
                ->setParameter('nowLive', $now);
        } elseif ($statut === 'upcoming') {
            $qb->andWhere('e.statut = :statutUpcoming')
                ->andWhere('e.commenceLe > :nowUpcoming')
                ->setParameter('statutUpcoming', Event::STATUS_PUBLISHED)
                ->setParameter('nowUpcoming', $now);
        } elseif ($statut !== null && $statut !== '') {
            $qb->andWhere('e.statut = :statut')
                ->setParameter('statut', $statut);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    
    public function searchMultiCriteriaWithPagination(
        string $idOrganisateur,
        ?string $nomLieu = null,
        ?\DateTimeInterface $dateDebut = null,
        ?\DateTimeInterface $dateFin = null,
        ?string $typeEvenementId = null,
        ?float $prixMin = null,
        ?float $prixMax = null,
        ?string $triPrix = null,
        int $page = 1,
        int $limit = 20,
        ?string $statut = null
    ): array {
        
        $offset = ($page - 1) * $limit;

        
        $items = $this->searchMultiCriteria(
            $idOrganisateur,
            $nomLieu,
            $dateDebut,
            $dateFin,
            $typeEvenementId,
            $prixMin,
            $prixMax,
            $triPrix,
            $limit,
            $offset,
            $statut
        );

        
        $total = $this->countSearchMultiCriteria(
            $idOrganisateur,
            $nomLieu,
            $dateDebut,
            $dateFin,
            $typeEvenementId,
            $prixMin,
            $prixMax,
            $statut
        );

        
        $totalPages = (int) ceil($total / $limit);
        $hasNext = $page < $totalPages;
        $hasPrev = $page > 1;

        return [
            'items' => $items,
            'pagination' => [
                'total' => $total,
                'page' => $page,
                'limit' => $limit,
                'totalPages' => $totalPages,
                'hasNext' => $hasNext,
                'hasPrev' => $hasPrev,
                'offset' => $offset,
                'startItem' => $total > 0 ? $offset + 1 : 0,
                'endItem' => min($offset + $limit, $total),
            ],
        ];
    }
}

