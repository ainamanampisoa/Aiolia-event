<?php

namespace App\Entity;

use App\Repository\Organisateur\BilletRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BilletRepository::class)]
#[ORM\Table(name: 'billets', schema: 'aiolia')]
#[ORM\HasLifecycleCallbacks]
class Billet
{
    public const STATUT_VALID = 'valid';
    public const STATUT_USED = 'used';
    public const STATUT_REFUNDED = 'refunded';
    public const STATUT_TRANSFERRED = 'transferred';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::BIGINT)]
    private ?string $id = null;

    #[ORM\ManyToOne(targetEntity: ElementCommande::class, inversedBy: 'billets')]
    #[ORM\JoinColumn(name: 'id_element_commande', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?ElementCommande $elementCommande = null;

    #[ORM\ManyToOne(targetEntity: TypeBillet::class)]
    #[ORM\JoinColumn(name: 'id_type_billet', referencedColumnName: 'id', nullable: false)]
    private ?TypeBillet $typeBillet = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'id_utilisateur_proprietaire', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?User $utilisateurProprietaire = null;

    #[ORM\Column(name: 'statut', type: Types::STRING, length: 20, options: ['default' => self::STATUT_VALID], columnDefinition: "ticket_status_enum NOT NULL DEFAULT 'valid'")]
    private string $statut = self::STATUT_VALID;

    #[ORM\Column(name: 'code_qr', type: Types::TEXT)]
    private string $codeQr;

    #[ORM\Column(name: 'checksum_qr', type: Types::TEXT, nullable: true)]
    private ?string $checksumQr = null;

    #[ORM\Column(name: 'emis_le', type: Types::DATETIMETZ_MUTABLE, options: ['default' => 'CURRENT_TIMESTAMP'])]
    private ?\DateTimeInterface $emisLe = null;

    #[ORM\Column(name: 'metadonnees', type: Types::JSON, nullable: true)]
    private ?array $metadonnees = null;

    #[ORM\PrePersist]
    public function initializeTimestamps(): void
    {
        $this->emisLe ??= new \DateTimeImmutable();
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getElementCommande(): ?ElementCommande
    {
        return $this->elementCommande;
    }

    public function setElementCommande(?ElementCommande $elementCommande): static
    {
        $this->elementCommande = $elementCommande;

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

    public function getUtilisateurProprietaire(): ?User
    {
        return $this->utilisateurProprietaire;
    }

    public function setUtilisateurProprietaire(?User $utilisateurProprietaire): static
    {
        $this->utilisateurProprietaire = $utilisateurProprietaire;

        return $this;
    }

    public function getStatut(): string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): static
    {
        $this->statut = $statut;

        return $this;
    }

    public function getCodeQr(): string
    {
        return $this->codeQr;
    }

    public function setCodeQr(string $codeQr): static
    {
        $this->codeQr = $codeQr;

        return $this;
    }

    public function getChecksumQr(): ?string
    {
        return $this->checksumQr;
    }

    public function setChecksumQr(?string $checksumQr): static
    {
        $this->checksumQr = $checksumQr;

        return $this;
    }

    public function getEmisLe(): ?\DateTimeInterface
    {
        return $this->emisLe;
    }

    public function setEmisLe(?\DateTimeInterface $emisLe): static
    {
        $this->emisLe = $emisLe;

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


