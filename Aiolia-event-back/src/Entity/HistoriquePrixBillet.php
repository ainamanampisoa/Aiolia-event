<?php

namespace App\Entity;

use App\Repository\Organisateur\HistoriquePrixBilletRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: HistoriquePrixBilletRepository::class)]
#[ORM\Table(name: 'historique_prix_billets', schema: 'aiolia')]
class HistoriquePrixBillet
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::BIGINT)]
    private ?string $id = null;

    #[ORM\ManyToOne(targetEntity: TypeBillet::class)]
    #[ORM\JoinColumn(name: 'id_type_billet', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?TypeBillet $typeBillet = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'modifie_par', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?User $modifiePar = null;

    #[ORM\Column(name: 'prix_precedent', type: Types::DECIMAL, precision: 12, scale: 2, nullable: true)]
    private ?string $prixPrecedent = null;

    #[ORM\Column(name: 'nouveau_prix', type: Types::DECIMAL, precision: 12, scale: 2, nullable: true)]
    private ?string $nouveauPrix = null;

    #[ORM\Column(name: 'modifie_le', type: Types::DATETIMETZ_MUTABLE)]
    private ?\DateTimeInterface $modifieLe = null;

    #[ORM\Column(name: 'raison', type: Types::TEXT, nullable: true)]
    private ?string $raison = null;

    #[ORM\Column(name: 'metadonnees', type: Types::JSON, nullable: true)]
    private ?array $metadonnees = null;

    public function __construct()
    {
        $this->modifieLe = new \DateTimeImmutable();
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

    public function getModifiePar(): ?User
    {
        return $this->modifiePar;
    }

    public function setModifiePar(?User $modifiePar): static
    {
        $this->modifiePar = $modifiePar;

        return $this;
    }

    public function getPrixPrecedent(): ?string
    {
        return $this->prixPrecedent;
    }

    public function setPrixPrecedent(?string $prixPrecedent): static
    {
        $this->prixPrecedent = $prixPrecedent;

        return $this;
    }

    public function getNouveauPrix(): ?string
    {
        return $this->nouveauPrix;
    }

    public function setNouveauPrix(?string $nouveauPrix): static
    {
        $this->nouveauPrix = $nouveauPrix;

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

    public function getRaison(): ?string
    {
        return $this->raison;
    }

    public function setRaison(?string $raison): static
    {
        $this->raison = $raison;

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
}

