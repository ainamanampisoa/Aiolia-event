<?php

namespace App\Entity;

use App\Repository\Organisateur\CodePromotionnelRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CodePromotionnelRepository::class)]
#[ORM\Table(name: 'codes_promotionnels', schema: 'aiolia')]
#[ORM\HasLifecycleCallbacks]
class CodePromotionnel
{
    public const TYPE_PERCENT = 'percent';
    public const TYPE_AMOUNT = 'amount';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::BIGINT)]
    private ?string $id = null;

    #[ORM\ManyToOne(targetEntity: OrganizerProfile::class)]
    #[ORM\JoinColumn(name: 'id_profil_organisateur', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?OrganizerProfile $profilOrganisateur = null;

    #[ORM\Column(name: 'code', type: Types::TEXT, unique: true)]
    private string $code;

    #[ORM\Column(name: 'type_promotion', type: Types::STRING, length: 20, columnDefinition: "promotion_type_enum NOT NULL")]
    private string $typePromotion;

    #[ORM\Column(name: 'valeur', type: Types::DECIMAL, precision: 12, scale: 2)]
    private string $valeur;

    #[ORM\Column(name: 'utilisation_maximale_totale', type: Types::INTEGER, nullable: true)]
    private ?int $utilisationMaximaleTotale = null;

    #[ORM\Column(name: 'utilisation_maximale_par_utilisateur', type: Types::INTEGER, nullable: true)]
    private ?int $utilisationMaximaleParUtilisateur = null;

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

    public function getProfilOrganisateur(): ?OrganizerProfile
    {
        return $this->profilOrganisateur;
    }

    public function setProfilOrganisateur(?OrganizerProfile $profilOrganisateur): static
    {
        $this->profilOrganisateur = $profilOrganisateur;

        return $this;
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

    public function getTypePromotion(): string
    {
        return $this->typePromotion;
    }

    public function setTypePromotion(string $typePromotion): static
    {
        $this->typePromotion = $typePromotion;

        return $this;
    }

    public function getValeur(): string
    {
        return $this->valeur;
    }

    public function setValeur(string $valeur): static
    {
        $this->valeur = $valeur;

        return $this;
    }

    public function getUtilisationMaximaleTotale(): ?int
    {
        return $this->utilisationMaximaleTotale;
    }

    public function setUtilisationMaximaleTotale(?int $utilisationMaximaleTotale): static
    {
        $this->utilisationMaximaleTotale = $utilisationMaximaleTotale;

        return $this;
    }

    public function getUtilisationMaximaleParUtilisateur(): ?int
    {
        return $this->utilisationMaximaleParUtilisateur;
    }

    public function setUtilisationMaximaleParUtilisateur(?int $utilisationMaximaleParUtilisateur): static
    {
        $this->utilisationMaximaleParUtilisateur = $utilisationMaximaleParUtilisateur;

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

    public function __toString(): string
    {
        return $this->code ?? '';
    }
}

