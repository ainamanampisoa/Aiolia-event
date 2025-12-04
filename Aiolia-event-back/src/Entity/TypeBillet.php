<?php

namespace App\Entity;

use App\Repository\Organisateur\TypeBilletRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TypeBilletRepository::class)]
#[ORM\Table(name: 'types_billets', schema: 'aiolia')]
#[ORM\HasLifecycleCallbacks]
class TypeBillet
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::BIGINT)]
    private ?string $id = null;

    #[ORM\ManyToOne(targetEntity: Event::class)]
    #[ORM\JoinColumn(name: 'id_evenement', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?Event $evenement = null;

    #[ORM\ManyToOne(targetEntity: SessionEvenement::class)]
    #[ORM\JoinColumn(name: 'id_session', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?SessionEvenement $session = null;

    #[ORM\ManyToOne(targetEntity: ConfigurationCategorieBillet::class)]
    #[ORM\JoinColumn(name: 'id_configuration_categorie', referencedColumnName: 'id', nullable: false)]
    private ?ConfigurationCategorieBillet $configurationCategorie = null;

    #[ORM\ManyToOne(targetEntity: ConfigurationSegmentBillet::class)]
    #[ORM\JoinColumn(name: 'id_configuration_segment', referencedColumnName: 'id', nullable: false)]
    private ?ConfigurationSegmentBillet $configurationSegment = null;

    #[ORM\Column(name: 'nom', type: Types::TEXT)]
    private string $nom;

    #[ORM\Column(name: 'description', type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(name: 'devise', type: Types::STRING, length: 3, options: ['default' => 'MGA'], columnDefinition: "currency_code NOT NULL DEFAULT 'MGA'")]
    private string $devise = 'MGA';

    #[ORM\Column(name: 'prix_de_base', type: Types::DECIMAL, precision: 12, scale: 2)]
    private string $prixDeBase;

    #[ORM\Column(name: 'frais_service', type: Types::DECIMAL, precision: 12, scale: 2, options: ['default' => 0])]
    private string $fraisService = '0';

    #[ORM\Column(name: 'taux_tva', type: Types::DECIMAL, precision: 5, scale: 2, options: ['default' => 0])]
    private string $tauxTva = '0';

    #[ORM\Column(name: 'ventes_commencent_le', type: Types::DATETIMETZ_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $ventesCommencentLe = null;

    #[ORM\Column(name: 'ventes_se_terminent_le', type: Types::DATETIMETZ_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $ventesSeTerminentLe = null;

    #[ORM\Column(name: 'minimum_par_commande', type: Types::INTEGER, options: ['default' => 1])]
    private int $minimumParCommande = 1;

    #[ORM\Column(name: 'maximum_par_commande', type: Types::INTEGER, nullable: true)]
    private ?int $maximumParCommande = null;

    #[ORM\Column(name: 'metadonnees', type: Types::JSON, nullable: true)]
    private ?array $metadonnees = null;

    #[ORM\Column(name: 'cree_le', type: Types::DATETIMETZ_MUTABLE)]
    private ?\DateTimeInterface $creeLe = null;

    #[ORM\Column(name: 'modifie_le', type: Types::DATETIMETZ_MUTABLE)]
    private ?\DateTimeInterface $modifieLe = null;

    #[ORM\OneToOne(targetEntity: InventaireBillet::class, mappedBy: 'typeBillet', cascade: ['persist', 'remove'])]
    private ?InventaireBillet $inventaire = null;

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

    public function getId(): ?string
    {
        return $this->id;
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

    public function getSession(): ?SessionEvenement
    {
        return $this->session;
    }

    public function setSession(?SessionEvenement $session): static
    {
        $this->session = $session;

        return $this;
    }

    public function getConfigurationCategorie(): ?ConfigurationCategorieBillet
    {
        return $this->configurationCategorie;
    }

    public function setConfigurationCategorie(?ConfigurationCategorieBillet $configurationCategorie): static
    {
        $this->configurationCategorie = $configurationCategorie;

        return $this;
    }

    public function getConfigurationSegment(): ?ConfigurationSegmentBillet
    {
        return $this->configurationSegment;
    }

    public function setConfigurationSegment(?ConfigurationSegmentBillet $configurationSegment): static
    {
        $this->configurationSegment = $configurationSegment;

        return $this;
    }

    public function getNom(): string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

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

    public function getPrixDeBase(): string
    {
        return $this->prixDeBase;
    }

    public function setPrixDeBase(string $prixDeBase): static
    {
        $this->prixDeBase = $prixDeBase;

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

    public function getTauxTva(): string
    {
        return $this->tauxTva;
    }

    public function setTauxTva(string $tauxTva): static
    {
        $this->tauxTva = $tauxTva;

        return $this;
    }

    public function getVentesCommencentLe(): ?\DateTimeInterface
    {
        return $this->ventesCommencentLe;
    }

    public function setVentesCommencentLe(?\DateTimeInterface $ventesCommencentLe): static
    {
        $this->ventesCommencentLe = $ventesCommencentLe;

        return $this;
    }

    public function getVentesSeTerminentLe(): ?\DateTimeInterface
    {
        return $this->ventesSeTerminentLe;
    }

    public function setVentesSeTerminentLe(?\DateTimeInterface $ventesSeTerminentLe): static
    {
        $this->ventesSeTerminentLe = $ventesSeTerminentLe;

        return $this;
    }

    public function getMinimumParCommande(): int
    {
        return $this->minimumParCommande;
    }

    public function setMinimumParCommande(int $minimumParCommande): static
    {
        $this->minimumParCommande = $minimumParCommande;

        return $this;
    }

    public function getMaximumParCommande(): ?int
    {
        return $this->maximumParCommande;
    }

    public function setMaximumParCommande(?int $maximumParCommande): static
    {
        $this->maximumParCommande = $maximumParCommande;

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

    public function getModifieLe(): ?\DateTimeInterface
    {
        return $this->modifieLe;
    }

    public function setModifieLe(?\DateTimeInterface $modifieLe): static
    {
        $this->modifieLe = $modifieLe;

        return $this;
    }

    public function getInventaire(): ?InventaireBillet
    {
        return $this->inventaire;
    }

    public function setInventaire(?InventaireBillet $inventaire): static
    {
        $this->inventaire = $inventaire;

        return $this;
    }

    public function __toString(): string
    {
        return $this->nom ?? '';
    }
}

