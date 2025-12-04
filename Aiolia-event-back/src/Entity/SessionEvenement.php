<?php

namespace App\Entity;

use App\Repository\Organisateur\SessionEvenementRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SessionEvenementRepository::class)]
#[ORM\Table(name: 'sessions_evenements', schema: 'aiolia')]
#[ORM\HasLifecycleCallbacks]
class SessionEvenement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::BIGINT)]
    private ?string $id = null;

    #[ORM\ManyToOne(targetEntity: Event::class)]
    #[ORM\JoinColumn(name: 'id_evenement', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?Event $evenement = null;

    #[ORM\ManyToOne(targetEntity: EspaceLieu::class)]
    #[ORM\JoinColumn(name: 'id_espace_lieu', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?EspaceLieu $espaceLieu = null;

    #[ORM\Column(name: 'titre', type: Types::TEXT, nullable: true)]
    private ?string $titre = null;

    #[ORM\Column(name: 'description', type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(name: 'commence_le', type: Types::DATETIMETZ_MUTABLE)]
    private ?\DateTimeInterface $commenceLe = null;

    #[ORM\Column(name: 'se_termine_le', type: Types::DATETIMETZ_MUTABLE)]
    private ?\DateTimeInterface $seTermineLe = null;

    #[ORM\Column(name: 'capacite', type: Types::INTEGER, nullable: true)]
    private ?int $capacite = null;

    #[ORM\Column(name: 'localisation_override', type: Types::JSON, nullable: true)]
    private ?array $localisationOverride = null;

    #[ORM\Column(name: 'url_live', type: Types::TEXT, nullable: true)]
    private ?string $urlLive = null;

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

    public function getEvenement(): ?Event
    {
        return $this->evenement;
    }

    public function setEvenement(?Event $evenement): static
    {
        $this->evenement = $evenement;

        return $this;
    }

    public function getEspaceLieu(): ?EspaceLieu
    {
        return $this->espaceLieu;
    }

    public function setEspaceLieu(?EspaceLieu $espaceLieu): static
    {
        $this->espaceLieu = $espaceLieu;

        return $this;
    }

    public function getTitre(): ?string
    {
        return $this->titre;
    }

    public function setTitre(?string $titre): static
    {
        $this->titre = $titre;

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

    public function getCapacite(): ?int
    {
        return $this->capacite;
    }

    public function setCapacite(?int $capacite): static
    {
        $this->capacite = $capacite;

        return $this;
    }

    public function getLocalisationOverride(): ?array
    {
        return $this->localisationOverride;
    }

    public function setLocalisationOverride(?array $localisationOverride): static
    {
        $this->localisationOverride = $localisationOverride;

        return $this;
    }

    public function getUrlLive(): ?string
    {
        return $this->urlLive;
    }

    public function setUrlLive(?string $urlLive): static
    {
        $this->urlLive = $urlLive;

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

    public function __toString(): string
    {
        return $this->titre ?? '';
    }
}

