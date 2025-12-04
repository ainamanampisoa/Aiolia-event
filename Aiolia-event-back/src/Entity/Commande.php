<?php

namespace App\Entity;

use App\Repository\Organisateur\CommandeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CommandeRepository::class)]
#[ORM\Table(name: 'commandes', schema: 'aiolia')]
#[ORM\HasLifecycleCallbacks]
class Commande
{
    public const STATUT_PENDING = 'pending';
    public const STATUT_AWAITING_PAYMENT = 'awaiting_payment';
    public const STATUT_PAID = 'paid';
    public const STATUT_CANCELLED = 'cancelled';
    public const STATUT_REFUNDED = 'refunded';
    public const STATUT_FAILED = 'failed';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::BIGINT)]
    private ?string $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'id_utilisateur', referencedColumnName: 'id', nullable: false)]
    private ?User $utilisateur = null;

    #[ORM\ManyToOne(targetEntity: Panier::class)]
    #[ORM\JoinColumn(name: 'id_panier', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?Panier $panier = null;

    #[ORM\Column(name: 'statut', type: Types::STRING, length: 20, options: ['default' => self::STATUT_PENDING], columnDefinition: "order_status_enum NOT NULL DEFAULT 'pending'")]
    private string $statut = self::STATUT_PENDING;

    #[ORM\Column(name: 'montant_total', type: Types::DECIMAL, precision: 12, scale: 2)]
    private string $montantTotal;

    #[ORM\Column(name: 'montant_remise', type: Types::DECIMAL, precision: 12, scale: 2, options: ['default' => 0])]
    private string $montantRemise = '0';

    #[ORM\Column(name: 'devise', type: Types::STRING, length: 3, options: ['default' => 'MGA'], columnDefinition: "currency_code NOT NULL DEFAULT 'MGA'")]
    private string $devise = 'MGA';

    #[ORM\Column(name: 'code_promotion', type: Types::TEXT, nullable: true)]
    private ?string $codePromotion = null;

    #[ORM\Column(name: 'paiement_due_le', type: Types::DATETIMETZ_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $paiementDueLe = null;

    #[ORM\Column(name: 'notes', type: Types::TEXT, nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(name: 'cree_le', type: Types::DATETIMETZ_MUTABLE)]
    private ?\DateTimeInterface $creeLe = null;

    #[ORM\Column(name: 'modifie_le', type: Types::DATETIMETZ_MUTABLE)]
    private ?\DateTimeInterface $modifieLe = null;

    #[ORM\OneToMany(targetEntity: ElementCommande::class, mappedBy: 'commande', cascade: ['persist', 'remove'])]
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

    public function getPanier(): ?Panier
    {
        return $this->panier;
    }

    public function setPanier(?Panier $panier): static
    {
        $this->panier = $panier;

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

    public function getMontantTotal(): string
    {
        return $this->montantTotal;
    }

    public function setMontantTotal(string $montantTotal): static
    {
        $this->montantTotal = $montantTotal;

        return $this;
    }

    public function getMontantRemise(): string
    {
        return $this->montantRemise;
    }

    public function setMontantRemise(string $montantRemise): static
    {
        $this->montantRemise = $montantRemise;

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

    public function getCodePromotion(): ?string
    {
        return $this->codePromotion;
    }

    public function setCodePromotion(?string $codePromotion): static
    {
        $this->codePromotion = $codePromotion;

        return $this;
    }

    public function getPaiementDueLe(): ?\DateTimeInterface
    {
        return $this->paiementDueLe;
    }

    public function setPaiementDueLe(?\DateTimeInterface $paiementDueLe): static
    {
        $this->paiementDueLe = $paiementDueLe;

        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): static
    {
        $this->notes = $notes;

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
     * @return Collection<int, ElementCommande>
     */
    public function getElements(): Collection
    {
        return $this->elements;
    }

    public function addElement(ElementCommande $element): static
    {
        if (!$this->elements->contains($element)) {
            $this->elements->add($element);
            $element->setCommande($this);
        }

        return $this;
    }

    public function removeElement(ElementCommande $element): static
    {
        if ($this->elements->removeElement($element)) {
            if ($element->getCommande() === $this) {
                $element->setCommande(null);
            }
        }

        return $this;
    }
}


