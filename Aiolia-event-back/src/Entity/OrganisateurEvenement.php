<?php

namespace App\Entity;

use App\Repository\Organisateur\OrganisateurEvenementRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OrganisateurEvenementRepository::class)]
#[ORM\Table(name: 'organisateurs_evenements', schema: 'aiolia')]
#[ORM\HasLifecycleCallbacks]
class OrganisateurEvenement
{
    public const ROLE_CREATEUR = 'createur';
    public const ROLE_CO_ORGANISATEUR = 'co_organisateur';
    public const ROLE_MODERATEUR = 'moderateur';
    public const ROLE_CONTRIBUTEUR = 'contributeur';

    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Event::class, inversedBy: 'organisateursEvenements')]
    #[ORM\JoinColumn(name: 'id_evenement', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?Event $evenement = null;

    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: OrganizerProfile::class, inversedBy: 'organisateursEvenements')]
    #[ORM\JoinColumn(name: 'id_profil_organisateur', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?OrganizerProfile $profilOrganisateur = null;

    #[ORM\Column(name: 'role', type: Types::STRING, length: 50, options: ['default' => self::ROLE_CO_ORGANISATEUR])]
    private string $role = self::ROLE_CO_ORGANISATEUR;

    #[ORM\Column(name: 'permissions', type: Types::JSON, nullable: true)]
    private ?array $permissions = null;

    #[ORM\ManyToOne(targetEntity: OrganizerProfile::class)]
    #[ORM\JoinColumn(name: 'ajoute_par', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?OrganizerProfile $ajoutePar = null;

    #[ORM\Column(name: 'cree_le', type: Types::DATETIMETZ_MUTABLE)]
    private ?\DateTimeInterface $creeLe = null;

    #[ORM\Column(name: 'modifie_le', type: Types::DATETIMETZ_MUTABLE)]
    private ?\DateTimeInterface $modifieLe = null;

    #[ORM\PrePersist]
    public function initializeTimestamps(): void
    {
        $now = new \DateTimeImmutable();
        $this->creeLe ??= $now;
        $this->modifieLe = $now;
    }

    #[ORM\PreUpdate]
    public function updateModifiedAt(): void
    {
        $this->modifieLe = new \DateTimeImmutable();
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

    public function getProfilOrganisateur(): ?OrganizerProfile
    {
        return $this->profilOrganisateur;
    }

    public function setProfilOrganisateur(?OrganizerProfile $profilOrganisateur): static
    {
        $this->profilOrganisateur = $profilOrganisateur;

        return $this;
    }

    public function getRole(): string
    {
        return $this->role;
    }

    public function setRole(string $role): static
    {
        $this->role = $role;

        return $this;
    }

    public function getPermissions(): ?array
    {
        return $this->permissions;
    }

    public function setPermissions(?array $permissions): static
    {
        $this->permissions = $permissions;

        return $this;
    }

    public function getAjoutePar(): ?OrganizerProfile
    {
        return $this->ajoutePar;
    }

    public function setAjoutePar(?OrganizerProfile $ajoutePar): static
    {
        $this->ajoutePar = $ajoutePar;

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

