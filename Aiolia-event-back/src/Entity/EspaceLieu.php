<?php

namespace App\Entity;

use App\Repository\Organisateur\EspaceLieuRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EspaceLieuRepository::class)]
#[ORM\Table(name: 'espaces_lieux', schema: 'aiolia', uniqueConstraints: [
    new ORM\UniqueConstraint(name: 'uq_espaces_lieux_lieu_id', columns: ['id_lieu', 'id'])
])]
#[ORM\HasLifecycleCallbacks]
class EspaceLieu
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::BIGINT)]
    private ?string $id = null;

    #[ORM\ManyToOne(targetEntity: Venue::class, inversedBy: 'espaces')]
    #[ORM\JoinColumn(name: 'id_lieu', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?Venue $lieu = null;

    #[ORM\Column(name: 'nom', type: Types::TEXT)]
    private string $nom;

    #[ORM\Column(name: 'description', type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(name: 'capacite', type: Types::INTEGER, nullable: true)]
    private ?int $capacite = null;

    #[ORM\Column(name: 'plan', type: Types::JSON, nullable: true)]
    private ?array $plan = null;

    #[ORM\Column(name: 'equipements', type: Types::JSON, nullable: true)]
    private ?array $equipements = null;

    #[ORM\Column(name: 'est_par_defaut', type: Types::BOOLEAN, options: ['default' => false])]
    private bool $estParDefaut = false;

    #[ORM\Column(name: 'cree_le', type: Types::DATETIMETZ_MUTABLE)]
    private ?\DateTimeInterface $creeLe = null;

    #[ORM\Column(name: 'modifie_le', type: Types::DATETIMETZ_MUTABLE)]
    private ?\DateTimeInterface $modifieLe = null;

    #[ORM\PrePersist]
    public function initializeTimestamps(): void
    {
        $now = new \DateTimeImmutable();
        $this->creeLe ??= $now;
        $this->modifieLe ??= $now;
    }

    #[ORM\PreUpdate]
    public function updateModifiedAt(): void
    {
        $this->modifieLe = new \DateTimeImmutable();
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getLieu(): ?Venue
    {
        return $this->lieu;
    }

    public function setLieu(?Venue $lieu): static
    {
        $this->lieu = $lieu;

        return $this;
    }

    public function getNom(): string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getCapacite(): ?int
    {
        return $this->capacite;
    }

    public function setCapacite(?int $capacite): static
    {
        $this->capacite = $capacite;

        return $this;
    }

    public function getPlan(): ?array
    {
        return $this->plan;
    }

    public function setPlan(?array $plan): static
    {
        $this->plan = $plan;

        return $this;
    }

    public function getEquipements(): ?array
    {
        return $this->equipements;
    }

    public function setEquipements(?array $equipements): static
    {
        $this->equipements = $equipements;

        return $this;
    }

    public function isEstParDefaut(): bool
    {
        return $this->estParDefaut;
    }

    public function setEstParDefaut(bool $estParDefaut): static
    {
        $this->estParDefaut = $estParDefaut;

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

    public function getModifieLe(): ?\DateTimeInterface
    {
        return $this->modifieLe;
    }

    public function setModifieLe(?\DateTimeInterface $modifieLe): static
    {
        $this->modifieLe = $modifieLe;

        return $this;
    }

    public function __toString(): string
    {
        return $this->nom ?? '';
    }
}


