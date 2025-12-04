<?php

namespace App\Entity;

use App\Repository\Organisateur\ApplicationPromotionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ApplicationPromotionRepository::class)]
#[ORM\Table(name: 'applications_promotions', schema: 'aiolia', uniqueConstraints: [
    new ORM\UniqueConstraint(name: 'uq_applications_promotions_promotion_commande', columns: ['id_promotion', 'id_commande'])
])]
#[ORM\HasLifecycleCallbacks]
class ApplicationPromotion
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::BIGINT)]
    private ?string $id = null;

    #[ORM\ManyToOne(targetEntity: CodePromotionnel::class)]
    #[ORM\JoinColumn(name: 'id_promotion', referencedColumnName: 'id', nullable: false)]
    private ?CodePromotionnel $promotion = null;

    #[ORM\ManyToOne(targetEntity: Commande::class)]
    #[ORM\JoinColumn(name: 'id_commande', referencedColumnName: 'id', nullable: false)]
    private ?Commande $commande = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'id_utilisateur', referencedColumnName: 'id', nullable: false)]
    private ?User $utilisateur = null;

    #[ORM\Column(name: 'montant_remise', type: Types::DECIMAL, precision: 12, scale: 2, options: ['default' => 0])]
    private string $montantRemise = '0';

    #[ORM\Column(name: 'applique_le', type: Types::DATETIMETZ_MUTABLE, options: ['default' => 'CURRENT_TIMESTAMP'])]
    private ?\DateTimeInterface $appliqueLe = null;

    #[ORM\PrePersist]
    public function initializeTimestamps(): void
    {
        $this->appliqueLe ??= new \DateTimeImmutable();
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getPromotion(): ?CodePromotionnel
    {
        return $this->promotion;
    }

    public function setPromotion(?CodePromotionnel $promotion): static
    {
        $this->promotion = $promotion;

        return $this;
    }

    public function getCommande(): ?Commande
    {
        return $this->commande;
    }

    public function setCommande(?Commande $commande): static
    {
        $this->commande = $commande;

        return $this;
    }

    public function getUtilisateur(): ?User
    {
        return $this->utilisateur;
    }

    public function setUtilisateur(?User $utilisateur): static
    {
        $this->utilisateur = $utilisateur;

        return $this;
    }

    public function getMontantRemise(): string
    {
        return $this->montantRemise;
    }

    public function setMontantRemise(string $montantRemise): static
    {
        $this->montantRemise = $montantRemise;

        return $this;
    }

    public function getAppliqueLe(): ?\DateTimeInterface
    {
        return $this->appliqueLe;
    }

    public function setAppliqueLe(?\DateTimeInterface $appliqueLe): static
    {
        $this->appliqueLe = $appliqueLe;

        return $this;
    }
}

