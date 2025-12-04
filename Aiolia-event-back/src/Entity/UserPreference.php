<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'preferences_utilisateurs', schema: 'aiolia')]
#[ORM\HasLifecycleCallbacks]
class UserPreference
{
    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'id_utilisateur', referencedColumnName: 'id', nullable: false)]
    private ?User $user = null;

    #[ORM\Id]
    #[ORM\Column(name: 'cle_preference', type: Types::TEXT)]
    private string $clePreference;

    #[ORM\Column(name: 'valeur_preference', type: Types::JSON)]
    private array $valeurPreference = [];

    #[ORM\Column(name: 'modifie_le', type: Types::DATETIMETZ_MUTABLE)]
    private ?\DateTimeInterface $modifieLe = null;

    public function __construct()
    {
        $this->modifieLe = new \DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function refreshUpdatedAt(): void
    {
        $this->modifieLe = new \DateTimeImmutable();
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;
        return $this;
    }

    public function getClePreference(): string
    {
        return $this->clePreference;
    }

    public function setClePreference(string $clePreference): static
    {
        $this->clePreference = $clePreference;
        return $this;
    }

    public function getValeurPreference(): array
    {
        return $this->valeurPreference;
    }

    public function setValeurPreference(array $valeurPreference): static
    {
        $this->valeurPreference = $valeurPreference;
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

