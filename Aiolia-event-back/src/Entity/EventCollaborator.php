<?php

namespace App\Entity;

use App\Repository\EventCollaboratorRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EventCollaboratorRepository::class)]
#[ORM\Table(name: 'event_collaborators')]
class EventCollaborator
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::BIGINT)]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Event::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Event $event = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\Column(length: 20)]
    private string $role = 'editor';

    #[ORM\Column]
    private bool $canEditEvent = true;

    #[ORM\Column]
    private bool $canManageTickets = true;

    #[ORM\Column]
    private bool $canViewSales = true;

    #[ORM\Column]
    private bool $canManageTeam = false;

    #[ORM\Column]
    private bool $canSendNotifications = true;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $invitedBy = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $invitedAt = null;

    #[ORM\Column]
    private bool $isActive = true;

    public function __construct()
    {
        $this->invitedAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEvent(): ?Event
    {
        return $this->event;
    }

    public function setEvent(?Event $event): static
    {
        $this->event = $event;
        return $this;
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

    public function getRole(): string
    {
        return $this->role;
    }

    public function setRole(string $role): static
    {
        $this->role = $role;
        return $this;
    }

    public function canEditEvent(): bool
    {
        return $this->canEditEvent;
    }

    public function setCanEditEvent(bool $canEditEvent): static
    {
        $this->canEditEvent = $canEditEvent;
        return $this;
    }

    public function canManageTickets(): bool
    {
        return $this->canManageTickets;
    }

    public function setCanManageTickets(bool $canManageTickets): static
    {
        $this->canManageTickets = $canManageTickets;
        return $this;
    }

    public function canViewSales(): bool
    {
        return $this->canViewSales;
    }

    public function setCanViewSales(bool $canViewSales): static
    {
        $this->canViewSales = $canViewSales;
        return $this;
    }

    public function canManageTeam(): bool
    {
        return $this->canManageTeam;
    }

    public function setCanManageTeam(bool $canManageTeam): static
    {
        $this->canManageTeam = $canManageTeam;
        return $this;
    }

    public function canSendNotifications(): bool
    {
        return $this->canSendNotifications;
    }

    public function setCanSendNotifications(bool $canSendNotifications): static
    {
        $this->canSendNotifications = $canSendNotifications;
        return $this;
    }

    public function getInvitedBy(): ?User
    {
        return $this->invitedBy;
    }

    public function setInvitedBy(?User $invitedBy): static
    {
        $this->invitedBy = $invitedBy;
        return $this;
    }

    public function getInvitedAt(): ?\DateTimeInterface
    {
        return $this->invitedAt;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;
        return $this;
    }
}

