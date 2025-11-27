<?php

namespace App\Entity;

use App\Repository\AdminProfileRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AdminProfileRepository::class)]
#[ORM\Table(name: 'profils_admin', schema: 'aiolia', uniqueConstraints: [
    new ORM\UniqueConstraint(name: 'uq_profils_admin_utilisateur', columns: ['id_utilisateur'])
])]
#[ORM\HasLifecycleCallbacks]
class AdminProfile
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::BIGINT)]
    private ?string $id = null;

    #[ORM\OneToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'id_utilisateur', referencedColumnName: 'id', nullable: false, unique: true)]
    private ?User $utilisateur = null;

    #[ORM\Column(name: 'nom_affichage', type: Types::TEXT)]
    private string $nomAffichage;

    #[ORM\Column(name: 'nom_legal', type: Types::TEXT, nullable: true)]
    private ?string $nomLegal = null;

    #[ORM\Column(name: 'numero_tva', type: Types::TEXT, nullable: true)]
    private ?string $numeroTva = null;

    #[ORM\Column(name: 'email_support', type: Types::STRING, length: 255, nullable: true, columnDefinition: 'CITEXT')]
    private ?string $emailSupport = null;

    #[ORM\Column(name: 'telephone_support', type: Types::STRING, length: 20, nullable: true, columnDefinition: 'phone_e164')]
    private ?string $telephoneSupport = null;

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

    public function getUtilisateur(): ?User
    {
        return $this->utilisateur;
    }

    public function setUtilisateur(?User $utilisateur): static
    {
        $this->utilisateur = $utilisateur;

        return $this;
    }

    public function getNomAffichage(): string
    {
        return $this->nomAffichage;
    }

    public function setNomAffichage(string $nomAffichage): static
    {
        $this->nomAffichage = $nomAffichage;

        return $this;
    }

    public function getNomLegal(): ?string
    {
        return $this->nomLegal;
    }

    public function setNomLegal(?string $nomLegal): static
    {
        $this->nomLegal = $nomLegal;

        return $this;
    }

    public function getNumeroTva(): ?string
    {
        return $this->numeroTva;
    }

    public function setNumeroTva(?string $numeroTva): static
    {
        $this->numeroTva = $numeroTva;

        return $this;
    }

    public function getEmailSupport(): ?string
    {
        return $this->emailSupport;
    }

    public function setEmailSupport(?string $emailSupport): static
    {
        $this->emailSupport = $emailSupport;

        return $this;
    }

    public function getTelephoneSupport(): ?string
    {
        return $this->telephoneSupport;
    }

    public function setTelephoneSupport(?string $telephoneSupport): static
    {
        $this->telephoneSupport = $telephoneSupport;

        return $this;
    }

    public function getCreeLe(): ?\DateTimeInterface
    {
        return $this->creeLe;
    }

    public function setCreeLe(\DateTimeInterface $creeLe): static
    {
        $this->creeLe = $creeLe;

        return $this;
    }

    public function getModifieLe(): ?\DateTimeInterface
    {
        return $this->modifieLe;
    }

    public function setModifieLe(\DateTimeInterface $modifieLe): static
    {
        $this->modifieLe = $modifieLe;

        return $this;
    }
}

