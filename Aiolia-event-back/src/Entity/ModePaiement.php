<?php

namespace App\Entity;

use App\Repository\Organisateur\ModePaiementRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ModePaiementRepository::class)]
#[ORM\Table(name: 'modes_paiement', schema: 'aiolia')]
#[ORM\HasLifecycleCallbacks]
class ModePaiement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::BIGINT)]
    private ?string $id = null;

    #[ORM\Column(name: 'code', type: Types::STRING, length: 50, unique: true)]
    private string $code;

    #[ORM\Column(name: 'libelle', type: Types::STRING, length: 255)]
    private string $libelle;

    #[ORM\Column(name: 'description', type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(name: 'icone', type: Types::STRING, length: 120, nullable: true)]
    private ?string $icone = null;

    #[ORM\Column(name: 'est_actif', type: Types::BOOLEAN, options: ['default' => true])]
    private bool $estActif = true;

    #[ORM\Column(name: 'ordre_affichage', type: Types::INTEGER, options: ['default' => 0])]
    private int $ordreAffichage = 0;

    #[ORM\Column(name: 'metadonnees', type: Types::JSON, nullable: true)]
    private ?array $metadonnees = null;

    #[ORM\Column(name: 'cree_le', type: Types::DATETIMETZ_MUTABLE)]
    private ?\DateTimeInterface $creeLe = null;

    #[ORM\Column(name: 'modifie_le', type: Types::DATETIMETZ_MUTABLE)]
    private ?\DateTimeInterface $modifieLe = null;

    #[ORM\PrePersist]
    public function initializeTimestamps(): void
    {
        $this->creeLe ??= new \DateTimeImmutable();
        $this->modifieLe ??= new \DateTimeImmutable();
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

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $code): static
    {
        $this->code = $code;
        return $this;
    }

    public function getLibelle(): string
    {
        return $this->libelle;
    }

    public function setLibelle(string $libelle): static
    {
        $this->libelle = $libelle;
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

    public function getIcone(): ?string
    {
        return $this->icone;
    }

    public function setIcone(?string $icone): static
    {
        $this->icone = $icone;
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

    public function getOrdreAffichage(): int
    {
        return $this->ordreAffichage;
    }

    public function setOrdreAffichage(int $ordreAffichage): static
    {
        $this->ordreAffichage = $ordreAffichage;
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
}

