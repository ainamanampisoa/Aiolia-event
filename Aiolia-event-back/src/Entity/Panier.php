<?php

namespace App\Entity;

use App\Repository\Organisateur\PanierRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PanierRepository::class)]
#[ORM\Table(name: 'paniers', schema: 'aiolia')]
#[ORM\HasLifecycleCallbacks]
class Panier
{
    public const STATUT_ACTIVE = 'active';
    public const STATUT_CONVERTED = 'converted';
    public const STATUT_ABANDONED = 'abandoned';
    public const STATUT_EXPIRED = 'expired';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::BIGINT)]
    private ?string $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'id_utilisateur', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?User $utilisateur = null;

    #[ORM\Column(name: 'statut', type: Types::STRING, length: 20, options: ['default' => self::STATUT_ACTIVE], columnDefinition: "cart_status_enum NOT NULL DEFAULT 'active'")]
    private string $statut = self::STATUT_ACTIVE;

    #[ORM\Column(name: 'jeton_session', type: Types::TEXT, unique: true, nullable: true)]
    private ?string $jetonSession = null;

    #[ORM\Column(name: 'devise', type: Types::STRING, length: 3, options: ['default' => 'MGA'], columnDefinition: "currency_code NOT NULL DEFAULT 'MGA'")]
    private string $devise = 'MGA';

    #[ORM\Column(name: 'montant_total', type: Types::DECIMAL, precision: 12, scale: 2, options: ['default' => 0])]
    private string $montantTotal = '0';

    #[ORM\Column(name: 'expire_le', type: Types::DATETIMETZ_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $expireLe = null;

    #[ORM\Column(name: 'cree_le', type: Types::DATETIMETZ_MUTABLE)]
    private ?\DateTimeInterface $creeLe = null;

    #[ORM\Column(name: 'modifie_le', type: Types::DATETIMETZ_MUTABLE)]
    private ?\DateTimeInterface $modifieLe = null;

    #[ORM\OneToMany(targetEntity: ElementPanier::class, mappedBy: 'panier', cascade: ['persist', 'remove'])]
    private Collection $elements;

    #[ORM\PrePersist]
    public function initializeTimestamps(): void
    {
        $now = new \DateTimeImmutable();
        $this->creeLe ??= $now;
        $this->modifieLe ??= $now;
    }

    #[ORM\PreUpdate]
    public function updateModifiedAt(): void
    {
        $this->modifieLe = new \DateTimeImmutable();
    }

    public function __construct()
    {
        $this->elements = new ArrayCollection();
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

    public function getStatut(): string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): static
    {
        $this->statut = $statut;

        return $this;
    }

    public function getJetonSession(): ?string
    {
        return $this->jetonSession;
    }

    public function setJetonSession(?string $jetonSession): static
    {
        $this->jetonSession = $jetonSession;

        return $this;
    }

    public function getDevise(): string
    {
        return $this->devise;
    }

    public function setDevise(string $devise): static
    {
        $this->devise = $devise;

        return $this;
    }

    public function getMontantTotal(): string
    {
        return $this->montantTotal;
    }

    public function setMontantTotal(string $montantTotal): static
    {
        $this->montantTotal = $montantTotal;

        return $this;
    }

    public function getExpireLe(): ?\DateTimeInterface
    {
        return $this->expireLe;
    }

    public function setExpireLe(?\DateTimeInterface $expireLe): static
    {
        $this->expireLe = $expireLe;

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

    /**
     * @return Collection<int, ElementPanier>
     */
    public function getElements(): Collection
    {
        return $this->elements;
    }

    public function addElement(ElementPanier $element): static
    {
        if (!$this->elements->contains($element)) {
            $this->elements->add($element);
            $element->setPanier($this);
        }

        return $this;
    }

    public function removeElement(ElementPanier $element): static
    {
        if ($this->elements->removeElement($element)) {
            if ($element->getPanier() === $this) {
                $element->setPanier(null);
            }
        }

        return $this;
    }
}


