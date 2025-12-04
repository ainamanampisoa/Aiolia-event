<?php

namespace App\Entity;

use App\Repository\Organisateur\ElementCommandeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ElementCommandeRepository::class)]
#[ORM\Table(name: 'elements_commandes', schema: 'aiolia')]
#[ORM\HasLifecycleCallbacks]
class ElementCommande
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::BIGINT)]
    private ?string $id = null;

    #[ORM\ManyToOne(targetEntity: Commande::class, inversedBy: 'elements')]
    #[ORM\JoinColumn(name: 'id_commande', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?Commande $commande = null;

    #[ORM\ManyToOne(targetEntity: TypeBillet::class)]
    #[ORM\JoinColumn(name: 'id_type_billet', referencedColumnName: 'id', nullable: false)]
    private ?TypeBillet $typeBillet = null;

    #[ORM\Column(name: 'quantite', type: Types::INTEGER)]
    private int $quantite;

    #[ORM\Column(name: 'prix_unitaire', type: Types::DECIMAL, precision: 12, scale: 2)]
    private string $prixUnitaire;

    #[ORM\Column(name: 'frais_service', type: Types::DECIMAL, precision: 12, scale: 2, options: ['default' => 0])]
    private string $fraisService = '0';

    #[ORM\Column(name: 'montant_tva', type: Types::DECIMAL, precision: 12, scale: 2, options: ['default' => 0])]
    private string $montantTva = '0';

    #[ORM\Column(name: 'montant_total', type: Types::DECIMAL, precision: 12, scale: 2)]
    private string $montantTotal;

    #[ORM\Column(name: 'cree_le', type: Types::DATETIMETZ_MUTABLE)]
    private ?\DateTimeInterface $creeLe = null;

    #[ORM\OneToMany(targetEntity: Billet::class, mappedBy: 'elementCommande', cascade: ['persist', 'remove'])]
    private Collection $billets;

    #[ORM\PrePersist]
    public function initializeTimestamps(): void
    {
        $this->creeLe ??= new \DateTimeImmutable();
    }

    public function __construct()
    {
        $this->billets = new ArrayCollection();
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getCommande(): ?Commande
    {
        return $this->commande;
    }

    public function setCommande(?Commande $commande): static
    {
        $this->commande = $commande;

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

    public function getQuantite(): int
    {
        return $this->quantite;
    }

    public function setQuantite(int $quantite): static
    {
        $this->quantite = $quantite;

        return $this;
    }

    public function getPrixUnitaire(): string
    {
        return $this->prixUnitaire;
    }

    public function setPrixUnitaire(string $prixUnitaire): static
    {
        $this->prixUnitaire = $prixUnitaire;

        return $this;
    }

    public function getFraisService(): string
    {
        return $this->fraisService;
    }

    public function setFraisService(string $fraisService): static
    {
        $this->fraisService = $fraisService;

        return $this;
    }

    public function getMontantTva(): string
    {
        return $this->montantTva;
    }

    public function setMontantTva(string $montantTva): static
    {
        $this->montantTva = $montantTva;

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

    public function getCreeLe(): ?\DateTimeInterface
    {
        return $this->creeLe;
    }

    public function setCreeLe(?\DateTimeInterface $creeLe): static
    {
        $this->creeLe = $creeLe;

        return $this;
    }

    /**
     * @return Collection<int, Billet>
     */
    public function getBillets(): Collection
    {
        return $this->billets;
    }

    public function addBillet(Billet $billet): static
    {
        if (!$this->billets->contains($billet)) {
            $this->billets->add($billet);
            $billet->setElementCommande($this);
        }

        return $this;
    }

    public function removeBillet(Billet $billet): static
    {
        if ($this->billets->removeElement($billet)) {
            if ($billet->getElementCommande() === $this) {
                $billet->setElementCommande(null);
            }
        }

        return $this;
    }
}


