<?php

namespace App\Entity;

use App\Repository\Organisateur\ElementPanierRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ElementPanierRepository::class)]
#[ORM\Table(name: 'elements_paniers', schema: 'aiolia', uniqueConstraints: [
    new ORM\UniqueConstraint(name: 'uq_elements_paniers_panier_type', columns: ['id_panier', 'id_type_billet'])
])]
#[ORM\HasLifecycleCallbacks]
class ElementPanier
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::BIGINT)]
    private ?string $id = null;

    #[ORM\ManyToOne(targetEntity: Panier::class, inversedBy: 'elements')]
    #[ORM\JoinColumn(name: 'id_panier', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?Panier $panier = null;

    #[ORM\ManyToOne(targetEntity: TypeBillet::class)]
    #[ORM\JoinColumn(name: 'id_type_billet', referencedColumnName: 'id', nullable: false)]
    private ?TypeBillet $typeBillet = null;

    #[ORM\Column(name: 'quantite', type: Types::INTEGER)]
    private int $quantite;

    #[ORM\Column(name: 'prix_unitaire', type: Types::DECIMAL, precision: 12, scale: 2)]
    private string $prixUnitaire;

    #[ORM\Column(name: 'prix_total', type: Types::DECIMAL, precision: 12, scale: 2)]
    private string $prixTotal;

    #[ORM\Column(name: 'cree_le', type: Types::DATETIMETZ_MUTABLE)]
    private ?\DateTimeInterface $creeLe = null;

    #[ORM\PrePersist]
    public function initializeTimestamps(): void
    {
        $this->creeLe ??= new \DateTimeImmutable();
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getPanier(): ?Panier
    {
        return $this->panier;
    }

    public function setPanier(?Panier $panier): static
    {
        $this->panier = $panier;

        return $this;
    }

    public function getTypeBillet(): ?TypeBillet
    {
        return $this->typeBillet;
    }

    public function setTypeBillet(?TypeBillet $typeBillet): static
    {
        $this->typeBillet = $typeBillet;

        return $this;
    }

    public function getQuantite(): int
    {
        return $this->quantite;
    }

    public function setQuantite(int $quantite): static
    {
        $this->quantite = $quantite;

        return $this;
    }

    public function getPrixUnitaire(): string
    {
        return $this->prixUnitaire;
    }

    public function setPrixUnitaire(string $prixUnitaire): static
    {
        $this->prixUnitaire = $prixUnitaire;

        return $this;
    }

    public function getPrixTotal(): string
    {
        return $this->prixTotal;
    }

    public function setPrixTotal(string $prixTotal): static
    {
        $this->prixTotal = $prixTotal;

        return $this;
    }

    public function getCreeLe(): ?\DateTimeInterface
    {
        return $this->creeLe;
    }

    public function setCreeLe(?\DateTimeInterface $creeLe): static
    {
        $this->creeLe = $creeLe;

        return $this;
    }
}


