<?php

namespace App\Entity;

use App\Repository\ListeAttenteBilletRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ListeAttenteBilletRepository::class)]
#[ORM\Table(name: 'listes_attente_billets', schema: 'aiolia')]
#[ORM\HasLifecycleCallbacks]
class ListeAttenteBillet
{
    const STATUT_PENDING = 'pending';
    const STATUT_NOTIFIED = 'notified';
    const STATUT_FULFILLED = 'fulfilled';
    const STATUT_CANCELLED = 'cancelled';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Event::class, inversedBy: 'listesAttente')]
    #[ORM\JoinColumn(name: 'id_evenement', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?Event $evenement = null;

    #[ORM\ManyToOne(targetEntity: TypeBillet::class, inversedBy: 'listesAttente')]
    #[ORM\JoinColumn(name: 'id_type_billet', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?TypeBillet $typeBillet = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'listesAttente')]
    #[ORM\JoinColumn(name: 'id_utilisateur', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?User $utilisateur = null;

    #[ORM\Column]
    #[Assert\Positive(message: 'La quantité demandée doit être supérieure à 0')]
    private ?int $quantiteDemandee = null;

    #[ORM\Column(length: 20)]
    #[Assert\Choice(choices: [self::STATUT_PENDING, self::STATUT_NOTIFIED, self::STATUT_FULFILLED, self::STATUT_CANCELLED])]
    private ?string $statut = self::STATUT_PENDING;

    #[ORM\Column(nullable: true)]
    private ?int $position = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $creeLe = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $notifieLe = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $remplieLe = null;

    public function __construct()
    {
        $this->creeLe = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEvenement(): ?Evenement
    {
        return $this->evenement;
    }

    public function setEvenement(?Evenement $evenement): static
    {
        $this->evenement = $evenement;
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

    public function getUtilisateur(): ?User
    {
        return $this->utilisateur;
    }

    public function setUtilisateur(?User $utilisateur): static
    {
        $this->utilisateur = $utilisateur;
        return $this;
    }

    public function getQuantiteDemandee(): ?int
    {
        return $this->quantiteDemandee;
    }

    public function setQuantiteDemandee(int $quantiteDemandee): static
    {
        $this->quantiteDemandee = $quantiteDemandee;
        return $this;
    }

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): static
    {
        $this->statut = $statut;
        
        // Mettre à jour les dates en fonction du statut
        if ($statut === self::STATUT_NOTIFIED && !$this->notifieLe) {
            $this->notifieLe = new \DateTimeImmutable();
        } elseif ($statut === self::STATUT_FULFILLED && !$this->remplieLe) {
            $this->remplieLe = new \DateTimeImmutable();
        }
        
        return $this;
    }

    public function getPosition(): ?int
    {
        return $this->position;
    }

    public function setPosition(?int $position): static
    {
        $this->position = $position;
        return $this;
    }

    public function getCreeLe(): ?\DateTimeImmutable
    {
        return $this->creeLe;
    }

    public function setCreeLe(\DateTimeImmutable $creeLe): static
    {
        $this->creeLe = $creeLe;
        return $this;
    }

    public function getNotifieLe(): ?\DateTimeImmutable
    {
        return $this->notifieLe;
    }

    public function setNotifieLe(?\DateTimeImmutable $notifieLe): static
    {
        $this->notifieLe = $notifieLe;
        return $this;
    }

    public function getRemplieLe(): ?\DateTimeImmutable
    {
        return $this->remplieLe;
    }

    public function setRemplieLe(?\DateTimeImmutable $remplieLe): static
    {
        $this->remplieLe = $remplieLe;
        return $this;
    }

    public function isActive(): bool
    {
        return in_array($this->statut, [self::STATUT_PENDING, self::STATUT_NOTIFIED]);
    }

    #[ORM\PrePersist]
    public function setCreeLeValue(): void
    {
        if ($this->creeLe === null) {
            $this->creeLe = new \DateTimeImmutable();
        }
    }

    #[ORM\PreUpdate]
    public function updateTimestamps(): void
    {
        // Gestion automatique des dates en fonction du statut
        if ($this->statut === self::STATUT_NOTIFIED && $this->notifieLe === null) {
            $this->notifieLe = new \DateTimeImmutable();
        }
        if ($this->statut === self::STATUT_FULFILLED && $this->remplieLe === null) {
            $this->remplieLe = new \DateTimeImmutable();
        }
    }
}