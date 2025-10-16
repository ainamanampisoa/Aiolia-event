<?php

namespace App\Service;

use Symfony\Component\String\Slugger\SluggerInterface;
use Doctrine\ORM\EntityManagerInterface;

class SlugService
{
    public function __construct(
        private SluggerInterface $slugger,
        private EntityManagerInterface $entityManager
    ) {
    }

    /**
     * Génère un slug unique pour une entité
     */
    public function generateUniqueSlug(string $text, string $entityClass, int $excludeId = null): string
    {
        $slug = $this->slugger->slug($text)->lower()->toString();
        $originalSlug = $slug;
        $counter = 1;

        $repository = $this->entityManager->getRepository($entityClass);

        // Vérifier si le slug existe déjà
        while ($this->slugExists($repository, $slug, $excludeId)) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * Vérifie si un slug existe déjà
     */
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

    /**
     * Convertit un texte en slug
     */
    public function slugify(string $text): string
    {
        return $this->slugger->slug($text)->lower()->toString();
    }
}

