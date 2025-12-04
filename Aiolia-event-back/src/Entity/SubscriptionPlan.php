<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'plans_abonnements', schema: 'aiolia', uniqueConstraints: [
    new ORM\UniqueConstraint(name: 'uq_plans_abonnements_niveau', columns: ['niveau', 'periode_facturation'])
])]
#[ORM\HasLifecycleCallbacks]
class SubscriptionPlan
{
    public const LEVEL_BASIC = 'basic';
    public const LEVEL_PRO = 'pro';
    public const LEVEL_ENTERPRISE = 'enterprise';

    public const PERIOD_MONTHLY = 'monthly';
    public const PERIOD_QUARTERLY = 'quarterly';
    public const PERIOD_YEARLY = 'yearly';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::BIGINT)]
    private ?string $id = null;

    #[ORM\Column(name: 'code', type: Types::TEXT, unique: true)]
    private string $code;

    #[ORM\Column(name: 'nom', type: Types::TEXT)]
    private string $nom;

    #[ORM\Column(name: 'description', type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(name: 'niveau', type: Types::TEXT, options: ['default' => self::LEVEL_BASIC])]
    private string $niveau = self::LEVEL_BASIC;

    #[ORM\Column(name: 'periode_facturation', type: Types::TEXT)]
    private string $periodeFacturation;

    #[ORM\Column(name: 'nombre_periodes', type: Types::INTEGER, options: ['default' => 1])]
    private int $nombrePeriodes = 1;

    #[ORM\Column(name: 'devise', type: Types::STRING, length: 3, options: ['default' => 'MGA', 'fixed' => true])]
    private string $devise = 'MGA';

    #[ORM\Column(name: 'prix', type: Types::DECIMAL, precision: 12, scale: 2)]
    private string $prix;

    #[ORM\Column(name: 'taux_tva', type: Types::DECIMAL, precision: 5, scale: 2, options: ['default' => 20])]
    private string $tauxTva = '20.00';

    #[ORM\Column(name: 'fonctionnalites', type: Types::JSON, nullable: true)]
    private ?array $fonctionnalites = null;

    #[ORM\Column(name: 'ordre_affichage', type: Types::INTEGER, options: ['default' => 0])]
    private int $ordreAffichage = 0;

    #[ORM\Column(name: 'est_actif', type: Types::BOOLEAN, options: ['default' => true])]
    private bool $estActif = true;

    #[ORM\Column(name: 'cree_le', type: Types::DATETIMETZ_MUTABLE)]
    private ?\DateTimeInterface $creeLe = null;

    #[ORM\Column(name: 'modifie_le', type: Types::DATETIMETZ_MUTABLE)]
    private ?\DateTimeInterface $modifieLe = null;

    public function __construct()
    {
        $now = new \DateTimeImmutable();
        $this->creeLe = $now;
        $this->modifieLe = $now;
    }

    #[ORM\PrePersist]
    public function initializeTimestamps(): void
    {
        $now = new \DateTimeImmutable();
        $this->creeLe ??= $now;
        $this->modifieLe = $now;
    }

    #[ORM\PreUpdate]
    public function refreshUpdatedAt(): void
    {
        $this->modifieLe = new \DateTimeImmutable();
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $code): static
    {
        $this->code = $code;
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

    public function getNiveau(): string
    {
        return $this->niveau;
    }

    public function setNiveau(string $niveau): static
    {
        $this->niveau = $niveau;
        return $this;
    }

    public function getPeriodeFacturation(): string
    {
        return $this->periodeFacturation;
    }

    public function setPeriodeFacturation(string $periodeFacturation): static
    {
        $this->periodeFacturation = $periodeFacturation;
        return $this;
    }

    public function getNombrePeriodes(): int
    {
        return $this->nombrePeriodes;
    }

    public function setNombrePeriodes(int $nombrePeriodes): static
    {
        $this->nombrePeriodes = $nombrePeriodes;
        return $this;
    }

    public function getDevise(): string
    {
        return $this->devise;
    }

    public function setDevise(string $devise): static
    {
        $this->devise = $devise;
        return $this;
    }

    public function getPrix(): string
    {
        return $this->prix;
    }

    public function setPrix(string $prix): static
    {
        $this->prix = $prix;
        return $this;
    }

    public function getTauxTva(): string
    {
        return $this->tauxTva;
    }

    public function setTauxTva(string $tauxTva): static
    {
        $this->tauxTva = $tauxTva;
        return $this;
    }

    public function getFonctionnalites(): ?array
    {
        return $this->fonctionnalites;
    }

    public function setFonctionnalites(?array $fonctionnalites): static
    {
        $this->fonctionnalites = $fonctionnalites;
        return $this;
    }

    public function getOrdreAffichage(): int
    {
        return $this->ordreAffichage;
    }

    public function setOrdreAffichage(int $ordreAffichage): static
    {
        $this->ordreAffichage = $ordreAffichage;
        return $this;
    }

    public function isEstActif(): bool
    {
        return $this->estActif;
    }

    public function setEstActif(bool $estActif): static
    {
        $this->estActif = $estActif;
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
}

