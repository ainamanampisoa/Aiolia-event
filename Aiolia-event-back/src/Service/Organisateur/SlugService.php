<?php

namespace App\Service\Organisateur;

use Symfony\Component\String\Slugger\SluggerInterface;
use Doctrine\ORM\EntityManagerInterface;

class SlugService
{
    public function __construct(
        private SluggerInterface $slugger,
        private EntityManagerInterface $entityManager
    ) {
    }

    
    public function generateUniqueSlug(string $text, string $entityClass, int $excludeId = 0): string
    {
        $slug = $this->slugger->slug($text)->lower()->toString();
        $originalSlug = $slug;
        $counter = 1;

        $repository = $this->entityManager->getRepository($entityClass);

        
        while ($this->slugExists($repository, $slug, $excludeId)) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    
    private function slugExists($repository, string $slug, ?int $excludeId): bool
    {
        $qb = $repository->createQueryBuilder('e')
            ->select('COUNT(e.id)')
            ->where('e.slug = :slug')
            ->setParameter('slug', $slug);

        if ($excludeId) {
            $qb->andWhere('e.id != :id')
                ->setParameter('id', $excludeId);
        }

        return $qb->getQuery()->getSingleScalarResult() > 0;
    }

    
    public function slugify(string $text): string
    {
        return $this->slugger->slug($text)->lower()->toString();
    }
}

