<?php

namespace App\Entity;

use App\Repository\Organisateur\EventRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EventRepository::class)]
#[ORM\Table(name: 'evenements', schema: 'aiolia')]
#[ORM\HasLifecycleCallbacks]
class Event
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_ARCHIVED = 'archived';

    public const VISIBILITY_PUBLIC = 'public';
    public const VISIBILITY_PRIVATE = 'private';
    public const VISIBILITY_UNLISTED = 'unlisted';

    public const FORMAT_IN_PERSON = 'in_person';
    public const FORMAT_ONLINE = 'online';
    public const FORMAT_HYBRID = 'hybrid';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::BIGINT)]
    private ?string $id = null;

    #[ORM\ManyToOne(targetEntity: OrganizerProfile::class)]
    #[ORM\JoinColumn(name: 'id_profil_organisateur', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?OrganizerProfile $profilOrganisateur = null;

    #[ORM\ManyToOne(targetEntity: EventCategory::class)]
    #[ORM\JoinColumn(name: 'id_categorie_principale', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?EventCategory $categoriePrincipale = null;

    #[ORM\ManyToOne(targetEntity: EventType::class)]
    #[ORM\JoinColumn(name: 'id_type_evenement', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?EventType $typeEvenement = null;

    #[ORM\ManyToOne(targetEntity: Venue::class)]
    #[ORM\JoinColumn(name: 'id_lieu', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?Venue $lieu = null;

    #[ORM\ManyToOne(targetEntity: EspaceLieu::class)]
    #[ORM\JoinColumn(name: 'id_espace_principal', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?EspaceLieu $espacePrincipal = null;

    #[ORM\Column(name: 'slug', type: Types::STRING, length: 255, unique: true)]
    private string $slug;

    #[ORM\Column(name: 'titre', type: Types::STRING, length: 255)]
    private string $titre;

    #[ORM\Column(name: 'sous_titre', type: Types::STRING, length: 255, nullable: true)]
    private ?string $sousTitre = null;

    #[ORM\Column(name: 'resume', type: Types::TEXT, nullable: true)]
    private ?string $resume = null;

    #[ORM\Column(name: 'description', type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(name: 'url_image_couverture', type: Types::TEXT, nullable: true)]
    private ?string $urlImageCouverture = null;

    #[ORM\Column(name: 'statut', type: Types::STRING, length: 20, options: ['default' => self::STATUS_DRAFT], columnDefinition: "event_status_enum NOT NULL DEFAULT 'draft'")]
    private string $statut = self::STATUS_DRAFT;

    #[ORM\Column(name: 'visibilite', type: Types::STRING, length: 20, options: ['default' => self::VISIBILITY_PUBLIC], columnDefinition: "event_visibility_enum NOT NULL DEFAULT 'public'")]
    private string $visibilite = self::VISIBILITY_PUBLIC;

    #[ORM\Column(name: 'format_evenement', type: Types::STRING, length: 20, options: ['default' => self::FORMAT_IN_PERSON], columnDefinition: "event_format_enum NOT NULL DEFAULT 'in_person'")]
    private string $formatEvenement = self::FORMAT_IN_PERSON;

    #[ORM\Column(name: 'capacite', type: Types::INTEGER, nullable: true)]
    private ?int $capacite = null;

    #[ORM\Column(name: 'fuseau_horaire', type: Types::STRING, length: 64, options: ['default' => 'Indian/Antananarivo'])]
    private string $fuseauHoraire = 'Indian/Antananarivo';

    #[ORM\Column(name: 'localisation_override', type: Types::JSON, nullable: true)]
    private ?array $localisationOverride = null;

    #[ORM\Column(name: 'url_live', type: Types::TEXT, nullable: true)]
    private ?string $urlLive = null;

    #[ORM\Column(name: 'plateforme_streaming', type: Types::TEXT, nullable: true)]
    private ?string $plateformeStreaming = null;

    #[ORM\Column(name: 'commence_le', type: Types::DATETIMETZ_MUTABLE)]
    private ?\DateTimeInterface $commenceLe = null;

    #[ORM\Column(name: 'se_termine_le', type: Types::DATETIMETZ_MUTABLE)]
    private ?\DateTimeInterface $seTermineLe = null;

    #[ORM\Column(name: 'ventes_commencent_le', type: Types::DATETIMETZ_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $ventesCommencentLe = null;

    #[ORM\Column(name: 'ventes_se_terminent_le', type: Types::DATETIMETZ_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $ventesSeTerminentLe = null;

    #[ORM\Column(name: 'restriction_age', type: Types::STRING, length: 120, nullable: true)]
    private ?string $restrictionAge = null;

    #[ORM\Column(name: 'code_langue', type: Types::STRING, length: 10, options: ['default' => 'fr-FR'])]
    private string $codeLangue = 'fr-FR';

    #[ORM\Column(name: 'est_en_vedette', type: Types::BOOLEAN, options: ['default' => false])]
    private bool $estEnVedette = false;

    #[ORM\Column(name: 'est_mis_en_avant', type: Types::BOOLEAN, options: ['default' => false])]
    private bool $estMisEnAvant = false;

    #[ORM\Column(name: 'url_youtube', type: Types::TEXT, nullable: true)]
    private ?string $urlYoutube = null;

    #[ORM\Column(name: 'nom_lieu_texte', type: Types::TEXT, nullable: true)]
    private ?string $nomLieuTexte = null;

    #[ORM\Column(name: 'adresse_complete', type: Types::TEXT, nullable: true)]
    private ?string $adresseComplete = null;

    #[ORM\Column(name: 'tarif_unique', type: Types::BOOLEAN, options: ['default' => false])]
    private bool $tarifUnique = false;

    #[ORM\Column(name: 'code_qr', type: Types::TEXT, nullable: true)]
    private ?string $codeQr = null;

    #[ORM\Column(name: 'checksum_qr', type: Types::TEXT, nullable: true)]
    private ?string $checksumQr = null;

    #[ORM\Column(name: 'cree_le', type: Types::DATETIMETZ_MUTABLE)]
    private ?\DateTimeInterface $creeLe = null;

    #[ORM\Column(name: 'modifie_le', type: Types::DATETIMETZ_MUTABLE)]
    private ?\DateTimeInterface $modifieLe = null;

    #[ORM\PrePersist]
    public function initializeTimestamps(): void
    {
        $now = new \DateTimeImmutable();
        $this->creeLe ??= $now;
        $this->modifieLe ??= $now;
    }

    #[ORM\OneToMany(targetEntity: LienLangueEvenement::class, mappedBy: 'evenement', cascade: ['persist', 'remove'])]
    private Collection $liensLangues;

    #[ORM\OneToMany(targetEntity: LienAccessibiliteEvenement::class, mappedBy: 'evenement', cascade: ['persist', 'remove'])]
    private Collection $liensAccessibilites;

    #[ORM\PreUpdate]
    public function updateModifiedAt(): void
    {
        $this->modifieLe = new \DateTimeImmutable();
    }

    public function __construct()
    {
        $this->liensLangues = new ArrayCollection();
        $this->liensAccessibilites = new ArrayCollection();
    }

    // Getters et Setters pour les relations
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

    public function getCategoriePrincipale(): ?EventCategory
    {
        return $this->categoriePrincipale;
    }

    public function setCategoriePrincipale(?EventCategory $categoriePrincipale): static
    {
        $this->categoriePrincipale = $categoriePrincipale;

        return $this;
    }

    public function getTypeEvenement(): ?EventType
    {
        return $this->typeEvenement;
    }

    public function setTypeEvenement(?EventType $typeEvenement): static
    {
        $this->typeEvenement = $typeEvenement;

        return $this;
    }

    public function getLieu(): ?Venue
    {
        return $this->lieu;
    }

    public function setLieu(?Venue $lieu): static
    {
        $this->lieu = $lieu;

        return $this;
    }

    public function getEspacePrincipal(): ?EspaceLieu
    {
        return $this->espacePrincipal;
    }

    public function setEspacePrincipal(?EspaceLieu $espacePrincipal): static
    {
        $this->espacePrincipal = $espacePrincipal;

        return $this;
    }

    // Getters et Setters pour les propriétés de base
    public function getSlug(): string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): static
    {
        $this->slug = $slug;

        return $this;
    }

    public function getTitre(): string
    {
        return $this->titre;
    }

    public function setTitre(string $titre): static
    {
        $this->titre = $titre;

        return $this;
    }

    public function getSousTitre(): ?string
    {
        return $this->sousTitre;
    }

    public function setSousTitre(?string $sousTitre): static
    {
        $this->sousTitre = $sousTitre;

        return $this;
    }

    public function getResume(): ?string
    {
        return $this->resume;
    }

    public function setResume(?string $resume): static
    {
        $this->resume = $resume;

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

    public function getUrlImageCouverture(): ?string
    {
        return $this->urlImageCouverture;
    }

    public function setUrlImageCouverture(?string $urlImageCouverture): static
    {
        $this->urlImageCouverture = $urlImageCouverture;

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

    public function getVisibilite(): string
    {
        return $this->visibilite;
    }

    public function setVisibilite(string $visibilite): static
    {
        $this->visibilite = $visibilite;

        return $this;
    }

    public function getFormatEvenement(): string
    {
        return $this->formatEvenement;
    }

    public function setFormatEvenement(string $formatEvenement): static
    {
        $this->formatEvenement = $formatEvenement;

        return $this;
    }

    public function getCapacite(): ?int
    {
        return $this->capacite;
    }

    public function setCapacite(?int $capacite): static
    {
        $this->capacite = $capacite;

        return $this;
    }

    public function getFuseauHoraire(): string
    {
        return $this->fuseauHoraire;
    }

    public function setFuseauHoraire(string $fuseauHoraire): static
    {
        $this->fuseauHoraire = $fuseauHoraire;

        return $this;
    }

    public function getLocalisationOverride(): ?array
    {
        return $this->localisationOverride;
    }

    public function setLocalisationOverride(?array $localisationOverride): static
    {
        $this->localisationOverride = $localisationOverride;

        return $this;
    }

    public function getUrlLive(): ?string
    {
        return $this->urlLive;
    }

    public function setUrlLive(?string $urlLive): static
    {
        $this->urlLive = $urlLive;

        return $this;
    }

    public function getPlateformeStreaming(): ?string
    {
        return $this->plateformeStreaming;
    }

    public function setPlateformeStreaming(?string $plateformeStreaming): static
    {
        $this->plateformeStreaming = $plateformeStreaming;

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

    public function getRestrictionAge(): ?string
    {
        return $this->restrictionAge;
    }

    public function setRestrictionAge(?string $restrictionAge): static
    {
        $this->restrictionAge = $restrictionAge;

        return $this;
    }

    public function getCodeLangue(): string
    {
        return $this->codeLangue;
    }

    public function setCodeLangue(string $codeLangue): static
    {
        $this->codeLangue = $codeLangue;

        return $this;
    }

    public function isEstEnVedette(): bool
    {
        return $this->estEnVedette;
    }

    public function setEstEnVedette(bool $estEnVedette): static
    {
        $this->estEnVedette = $estEnVedette;

        return $this;
    }

    public function isEstMisEnAvant(): bool
    {
        return $this->estMisEnAvant;
    }

    public function setEstMisEnAvant(bool $estMisEnAvant): static
    {
        $this->estMisEnAvant = $estMisEnAvant;

        return $this;
    }

    public function getUrlYoutube(): ?string
    {
        return $this->urlYoutube;
    }

    public function setUrlYoutube(?string $urlYoutube): static
    {
        $this->urlYoutube = $urlYoutube;

        return $this;
    }

    public function getNomLieuTexte(): ?string
    {
        return $this->nomLieuTexte;
    }

    public function setNomLieuTexte(?string $nomLieuTexte): static
    {
        $this->nomLieuTexte = $nomLieuTexte;

        return $this;
    }

    public function getAdresseComplete(): ?string
    {
        return $this->adresseComplete;
    }

    public function setAdresseComplete(?string $adresseComplete): static
    {
        $this->adresseComplete = $adresseComplete;

        return $this;
    }

    public function isTarifUnique(): bool
    {
        return $this->tarifUnique;
    }

    public function setTarifUnique(bool $tarifUnique): static
    {
        $this->tarifUnique = $tarifUnique;

        return $this;
    }

    public function getCodeQr(): ?string
    {
        return $this->codeQr;
    }

    public function setCodeQr(?string $codeQr): static
    {
        $this->codeQr = $codeQr;

        return $this;
    }

    public function getChecksumQr(): ?string
    {
        return $this->checksumQr;
    }

    public function setChecksumQr(?string $checksumQr): static
    {
        $this->checksumQr = $checksumQr;

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
     * @return Collection<int, LienLangueEvenement>
     */
    public function getLiensLangues(): Collection
    {
        return $this->liensLangues;
    }

    public function addLienLangue(LienLangueEvenement $lienLangue): static
    {
        if (!$this->liensLangues->contains($lienLangue)) {
            $this->liensLangues->add($lienLangue);
            $lienLangue->setEvenement($this);
        }

        return $this;
    }

    public function removeLienLangue(LienLangueEvenement $lienLangue): static
    {
        if ($this->liensLangues->removeElement($lienLangue)) {
            if ($lienLangue->getEvenement() === $this) {
                $lienLangue->setEvenement(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, LienAccessibiliteEvenement>
     */
    public function getLiensAccessibilites(): Collection
    {
        return $this->liensAccessibilites;
    }

    public function addLienAccessibilite(LienAccessibiliteEvenement $lienAccessibilite): static
    {
        if (!$this->liensAccessibilites->contains($lienAccessibilite)) {
            $this->liensAccessibilites->add($lienAccessibilite);
            $lienAccessibilite->setEvenement($this);
        }

        return $this;
    }

    public function removeLienAccessibilite(LienAccessibiliteEvenement $lienAccessibilite): static
    {
        if ($this->liensAccessibilites->removeElement($lienAccessibilite)) {
            if ($lienAccessibilite->getEvenement() === $this) {
                $lienAccessibilite->setEvenement(null);
            }
        }

        return $this;
    }

    public function __toString(): string
    {
        return $this->titre ?? '';
    }
}

