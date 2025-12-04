<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'statistiques_evenements_utilisateurs', schema: 'aiolia')]
#[ORM\HasLifecycleCallbacks]
class UserEventStatistics
{
    #[ORM\Id]
    #[ORM\OneToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'id_utilisateur', referencedColumnName: 'id', nullable: false)]
    private ?User $user = null;

    #[ORM\Column(name: 'evenements_auxquels_a_participe', type: Types::INTEGER, options: ['default' => 0])]
    private int $evenementsAuxquelsAParticipe = 0;

    #[ORM\Column(name: 'evenements_a_venir', type: Types::INTEGER, options: ['default' => 0])]
    private int $evenementsAVenir = 0;

    #[ORM\Column(name: 'depenses_totales', type: Types::DECIMAL, precision: 12, scale: 2, options: ['default' => 0])]
    private string $depensesTotales = '0.00';

    #[ORM\Column(name: 'categories_favorites', type: Types::SIMPLE_ARRAY, nullable: true)]
    private ?array $categoriesFavorites = null;

    #[ORM\Column(name: 'dernier_evenement_le', type: Types::DATETIMETZ_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dernierEvenementLe = null;

    #[ORM\Column(name: 'modifie_le', type: Types::DATETIMETZ_MUTABLE)]
    private ?\DateTimeInterface $modifieLe = null;

    public function __construct()
    {
        $this->modifieLe = new \DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function refreshUpdatedAt(): void
    {
        $this->modifieLe = new \DateTimeImmutable();
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;
        return $this;
    }

    public function getEvenementsAuxquelsAParticipe(): int
    {
        return $this->evenementsAuxquelsAParticipe;
    }

    public function setEvenementsAuxquelsAParticipe(int $evenementsAuxquelsAParticipe): static
    {
        $this->evenementsAuxquelsAParticipe = $evenementsAuxquelsAParticipe;
        return $this;
    }

    public function getEvenementsAVenir(): int
    {
        return $this->evenementsAVenir;
    }

    public function setEvenementsAVenir(int $evenementsAVenir): static
    {
        $this->evenementsAVenir = $evenementsAVenir;
        return $this;
    }

    public function getDepensesTotales(): string
    {
        return $this->depensesTotales;
    }

    public function setDepensesTotales(string $depensesTotales): static
    {
        $this->depensesTotales = $depensesTotales;
        return $this;
    }

    public function getCategoriesFavorites(): ?array
    {
        return $this->categoriesFavorites;
    }

    public function setCategoriesFavorites(?array $categoriesFavorites): static
    {
        $this->categoriesFavorites = $categoriesFavorites;
        return $this;
    }

    public function getDernierEvenementLe(): ?\DateTimeInterface
    {
        return $this->dernierEvenementLe;
    }

    public function setDernierEvenementLe(?\DateTimeInterface $dernierEvenementLe): static
    {
        $this->dernierEvenementLe = $dernierEvenementLe;
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
}

