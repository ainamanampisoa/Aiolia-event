<?php

namespace App\Entity;

use App\Repository\Organisateur\RegleTarificationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RegleTarificationRepository::class)]
#[ORM\Table(name: 'regles_tarification', schema: 'aiolia')]
#[ORM\HasLifecycleCallbacks]
class RegleTarification
{
    public const TYPE_TIER = 'tier';
    public const TYPE_TIME_WINDOW = 'time_window';
    public const TYPE_PROMO = 'promo';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::BIGINT)]
    private ?string $id = null;

    #[ORM\ManyToOne(targetEntity: TypeBillet::class)]
    #[ORM\JoinColumn(name: 'id_type_billet', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?TypeBillet $typeBillet = null;

    #[ORM\Column(name: 'type_regle', type: Types::STRING, length: 20, columnDefinition: "pricing_rule_type_enum NOT NULL")]
    private string $typeRegle;

    #[ORM\Column(name: 'valeur_seuil', type: Types::DECIMAL, precision: 12, scale: 2, nullable: true)]
    private ?string $valeurSeuil = null;

    #[ORM\Column(name: 'valeur', type: Types::DECIMAL, precision: 12, scale: 2, nullable: true)]
    private ?string $valeur = null;

    #[ORM\Column(name: 'commence_le', type: Types::DATETIMETZ_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $commenceLe = null;

    #[ORM\Column(name: 'se_termine_le', type: Types::DATETIMETZ_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $seTermineLe = null;

    #[ORM\Column(name: 'metadonnees', type: Types::JSON, nullable: true)]
    private ?array $metadonnees = null;

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

    public function getTypeBillet(): ?TypeBillet
    {
        return $this->typeBillet;
    }

    public function setTypeBillet(?TypeBillet $typeBillet): static
    {
        $this->typeBillet = $typeBillet;

        return $this;
    }

    public function getTypeRegle(): string
    {
        return $this->typeRegle;
    }

    public function setTypeRegle(string $typeRegle): static
    {
        $this->typeRegle = $typeRegle;

        return $this;
    }

    public function getValeurSeuil(): ?string
    {
        return $this->valeurSeuil;
    }

    public function setValeurSeuil(?string $valeurSeuil): static
    {
        $this->valeurSeuil = $valeurSeuil;

        return $this;
    }

    public function getValeur(): ?string
    {
        return $this->valeur;
    }

    public function setValeur(?string $valeur): static
    {
        $this->valeur = $valeur;

        return $this;
    }

    public function getCommenceLe(): ?\DateTimeInterface
    {
        return $this->commenceLe;
    }

    public function setCommenceLe(?\DateTimeInterface $commenceLe): static
    {
        $this->commenceLe = $commenceLe;

        return $this;
    }

    public function getSeTermineLe(): ?\DateTimeInterface
    {
        return $this->seTermineLe;
    }

    public function setSeTermineLe(?\DateTimeInterface $seTermineLe): static
    {
        $this->seTermineLe = $seTermineLe;

        return $this;
    }

    public function getMetadonnees(): ?array
    {
        return $this->metadonnees;
    }

    public function setMetadonnees(?array $metadonnees): static
    {
        $this->metadonnees = $metadonnees;

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

    public function isActive(): bool
    {
        $now = new \DateTimeImmutable();
        
        if ($this->commenceLe !== null && $now < $this->commenceLe) {
            return false;
        }
        
        if ($this->seTermineLe !== null && $now > $this->seTermineLe) {
            return false;
        }
        
        return true;
    }
}


