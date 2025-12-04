<?php

namespace App\Entity;

use App\Enum\BilletSegmentEnum;
use App\Repository\Organisateur\ConfigurationSegmentBilletRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ConfigurationSegmentBilletRepository::class)]
#[ORM\Table(name: 'configuration_segments_billets', schema: 'aiolia')]
#[ORM\HasLifecycleCallbacks]
class ConfigurationSegmentBillet
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::BIGINT)]
    private ?string $id = null;

    #[ORM\Column(name: 'nom', type: Types::STRING, length: 20, unique: true, options: ['default' => BilletSegmentEnum::TOUS], columnDefinition: "billet_segment_enum NOT NULL")]
    private string $nom = BilletSegmentEnum::TOUS;

    #[ORM\Column(name: 'age_min', type: Types::INTEGER, nullable: true)]
    private ?int $ageMin = null;

    #[ORM\Column(name: 'age_max', type: Types::INTEGER, nullable: true)]
    private ?int $ageMax = null;

    #[ORM\Column(name: 'pourcentage_prix', type: Types::DECIMAL, precision: 5, scale: 2, options: ['default' => 0])]
    private string $pourcentagePrix = '0';

    #[ORM\Column(name: 'est_actif', type: Types::BOOLEAN, options: ['default' => true])]
    private bool $estActif = true;

    #[ORM\Column(name: 'metadonnees', type: Types::JSON, nullable: true)]
    private ?array $metadonnees = null;

    #[ORM\Column(name: 'cree_le', type: Types::DATETIMETZ_MUTABLE)]
    private ?\DateTimeInterface $creeLe = null;

    #[ORM\Column(name: 'modifie_le', type: Types::DATETIMETZ_MUTABLE)]
    private ?\DateTimeInterface $modifieLe = null;

    #[ORM\Column(name: 'supprime_le', type: Types::DATETIMETZ_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $supprimeLe = null;

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

    public function getNom(): string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        BilletSegmentEnum::assertValid($nom);
        $this->nom = $nom;

        return $this;
    }

    public function getAgeMin(): ?int
    {
        return $this->ageMin;
    }

    public function setAgeMin(?int $ageMin): static
    {
        $this->ageMin = $ageMin;

        return $this;
    }

    public function getAgeMax(): ?int
    {
        return $this->ageMax;
    }

    public function setAgeMax(?int $ageMax): static
    {
        $this->ageMax = $ageMax;

        return $this;
    }

    public function getPourcentagePrix(): string
    {
        return $this->pourcentagePrix;
    }

    public function setPourcentagePrix(string $pourcentagePrix): static
    {
        $this->pourcentagePrix = $pourcentagePrix;

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

    public function getModifieLe(): ?\DateTimeInterface
    {
        return $this->modifieLe;
    }

    public function setModifieLe(?\DateTimeInterface $modifieLe): static
    {
        $this->modifieLe = $modifieLe;

        return $this;
    }

    public function getSupprimeLe(): ?\DateTimeInterface
    {
        return $this->supprimeLe;
    }

    public function setSupprimeLe(?\DateTimeInterface $supprimeLe): static
    {
        $this->supprimeLe = $supprimeLe;

        return $this;
    }

    public function isDeleted(): bool
    {
        return $this->supprimeLe !== null;
    }

    public function softDelete(): void
    {
        $this->supprimeLe = new \DateTimeImmutable();
    }

    public function restore(): void
    {
        $this->supprimeLe = null;
    }

    public function __toString(): string
    {
        return $this->nom ?? '';
    }
}

