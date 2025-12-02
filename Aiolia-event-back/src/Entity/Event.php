<?php

namespace App\Entity;

use App\Repository\Organisateur\EventRepository;
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

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::BIGINT)]
    private ?string $id = null;

    #[ORM\Column(name: 'id_profil_organisateur', type: Types::BIGINT, nullable: true)]
    private ?string $organizerProfileId = null;
    
    private ?User $organizer = null;

    #[ORM\ManyToOne(targetEntity: EventCategory::class)]
    #[ORM\JoinColumn(name: 'id_categorie_principale', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?EventCategory $primaryCategory = null;

    #[ORM\Column(name: 'id_lieu', type: Types::BIGINT, nullable: true)]
    private ?string $venueId = null;

    #[ORM\Column(name: 'slug', type: Types::STRING, length: 255, unique: true)]
    private string $slug;

    #[ORM\Column(name: 'titre', type: Types::STRING, length: 255)]
    private string $title;

    #[ORM\Column(name: 'sous_titre', type: Types::STRING, length: 255, nullable: true)]
    private ?string $subtitle = null;

    #[ORM\Column(name: 'resume', type: Types::TEXT, nullable: true)]
    private ?string $summary = null;

    #[ORM\Column(name: 'description', type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(name: 'url_image_couverture', type: Types::TEXT, nullable: true)]
    private ?string $coverImageUrl = null;

    #[ORM\Column(name: 'statut', type: Types::STRING, length: 20, options: ['default' => self::STATUS_DRAFT])]
    private string $status = self::STATUS_DRAFT;

    #[ORM\Column(name: 'visibilite', type: Types::STRING, length: 20, options: ['default' => self::VISIBILITY_PUBLIC])]
    private string $visibility = self::VISIBILITY_PUBLIC;

    #[ORM\Column(name: 'capacite', type: Types::INTEGER, nullable: true)]
    private ?int $capacity = null;

    #[ORM\Column(name: 'fuseau_horaire', type: Types::STRING, length: 64, options: ['default' => 'Indian/Antananarivo'])]
    private string $timezone = 'Indian/Antananarivo';

    #[ORM\Column(name: 'commence_le', type: Types::DATETIMETZ_MUTABLE)]
    private \DateTimeInterface $startsAt;

    #[ORM\Column(name: 'se_termine_le', type: Types::DATETIMETZ_MUTABLE)]
    private \DateTimeInterface $endsAt;

    #[ORM\Column(name: 'ventes_commencent_le', type: Types::DATETIMETZ_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $salesStartsAt = null;

    #[ORM\Column(name: 'ventes_se_terminent_le', type: Types::DATETIMETZ_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $salesEndsAt = null;

    #[ORM\Column(name: 'restriction_age', type: Types::STRING, length: 120, nullable: true)]
    private ?string $ageRestriction = null;

    #[ORM\Column(name: 'code_langue', type: Types::STRING, length: 10, options: ['default' => 'fr-FR'])]
    private string $languageCode = 'fr-FR';

    #[ORM\Column(name: 'est_en_vedette', type: Types::BOOLEAN, options: ['default' => false])]
    private bool $isFeatured = false;

    #[ORM\Column(name: 'est_mis_en_avant', type: Types::BOOLEAN, options: ['default' => false])]
    private bool $isHighlighted = false;

    #[ORM\Column(name: 'url_youtube', type: Types::TEXT, nullable: true)]
    private ?string $youtubeUrl = null;

    #[ORM\Column(name: 'nom_lieu_texte', type: Types::TEXT, nullable: true)]
    private ?string $venueNameText = null;

    #[ORM\Column(name: 'adresse_complete', type: Types::TEXT, nullable: true)]
    private ?string $fullAddress = null;

    #[ORM\Column(name: 'tarif_unique', type: Types::BOOLEAN, options: ['default' => false])]
    private bool $singlePrice = false;

    #[ORM\Column(name: 'cree_le', type: Types::DATETIMETZ_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(name: 'modifie_le', type: Types::DATETIMETZ_MUTABLE)]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\ManyToMany(targetEntity: EventTag::class)]
    #[ORM\JoinTable(
        name: 'liens_tags_evenements',
        schema: 'aiolia',
        joinColumns: [new ORM\JoinColumn(name: 'id_evenement', referencedColumnName: 'id')],
        inverseJoinColumns: [new ORM\JoinColumn(name: 'id_tag', referencedColumnName: 'id')]
    )]
    private \Doctrine\Common\Collections\Collection $tags;

    #[ORM\OneToMany(targetEntity: EventLanguage::class, mappedBy: 'event', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private \Doctrine\Common\Collections\Collection $languages;

    #[ORM\OneToMany(targetEntity: EventAccessibility::class, mappedBy: 'event', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private \Doctrine\Common\Collections\Collection $accessibilities;

    #[ORM\OneToMany(targetEntity: EventPaymentMethod::class, mappedBy: 'event', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private \Doctrine\Common\Collections\Collection $paymentMethods;

    #[ORM\OneToMany(targetEntity: EventMedia::class, mappedBy: 'event', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private \Doctrine\Common\Collections\Collection $media;

    public function __construct()
    {
        $now = new \DateTimeImmutable();
        $this->createdAt = $now;
        $this->updatedAt = $now;
        $this->tags = new \Doctrine\Common\Collections\ArrayCollection();
        $this->languages = new \Doctrine\Common\Collections\ArrayCollection();
        $this->accessibilities = new \Doctrine\Common\Collections\ArrayCollection();
        $this->paymentMethods = new \Doctrine\Common\Collections\ArrayCollection();
        $this->media = new \Doctrine\Common\Collections\ArrayCollection();
    }

    #[ORM\PrePersist]
    public function initializeTimestamps(): void
    {
        $now = new \DateTimeImmutable();
        $this->createdAt ??= $now;
        $this->updatedAt = $now;
    }

    #[ORM\PreUpdate]
    public function touchUpdatedAt(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getOrganizerProfileId(): ?string
    {
        return $this->organizerProfileId;
    }

    public function setOrganizerProfileId(?string $organizerProfileId): static
    {
        $this->organizerProfileId = $organizerProfileId;
        $this->organizer = null; // Réinitialiser le cache

        return $this;
    }

    public function getOrganizer(): ?User
    {
        return $this->organizer;
    }

    public function setOrganizer(?User $organizer): static
    {
        // Cette méthode est gardée pour la compatibilité mais ne modifie pas la base de données directement
        // Pour modifier l'organizer, il faut utiliser setOrganizerProfileId via organizer_profiles
        $this->organizer = $organizer;

        return $this;
    }

    public function getPrimaryCategory(): ?EventCategory
    {
        return $this->primaryCategory;
    }

    public function setPrimaryCategory(?EventCategory $primaryCategory): static
    {
        $this->primaryCategory = $primaryCategory;

        return $this;
    }

    public function getVenueId(): ?string
    {
        return $this->venueId;
    }

    public function setVenueId(?string $venueId): static
    {
        $this->venueId = $venueId;

        return $this;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): static
    {
        $this->slug = $slug;

        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getSubtitle(): ?string
    {
        return $this->subtitle;
    }

    public function setSubtitle(?string $subtitle): static
    {
        $this->subtitle = $subtitle;

        return $this;
    }

    public function getSummary(): ?string
    {
        return $this->summary;
    }

    public function setSummary(?string $summary): static
    {
        $this->summary = $summary;

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

    public function getCoverImageUrl(): ?string
    {
        return $this->coverImageUrl;
    }

    public function setCoverImageUrl(?string $coverImageUrl): static
    {
        $this->coverImageUrl = $coverImageUrl;

        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getVisibility(): string
    {
        return $this->visibility;
    }

    public function setVisibility(string $visibility): static
    {
        $this->visibility = $visibility;

        return $this;
    }

    public function getCapacity(): ?int
    {
        return $this->capacity;
    }

    public function setCapacity(?int $capacity): static
    {
        $this->capacity = $capacity;

        return $this;
    }

    public function getTimezone(): string
    {
        return $this->timezone;
    }

    public function setTimezone(string $timezone): static
    {
        $this->timezone = $timezone;

        return $this;
    }

    public function getStartsAt(): \DateTimeInterface
    {
        return $this->startsAt;
    }

    public function setStartsAt(\DateTimeInterface $startsAt): static
    {
        $this->startsAt = $startsAt;

        return $this;
    }

    public function getEndsAt(): \DateTimeInterface
    {
        return $this->endsAt;
    }

    public function setEndsAt(\DateTimeInterface $endsAt): static
    {
        $this->endsAt = $endsAt;

        return $this;
    }

    public function getSalesStartsAt(): ?\DateTimeInterface
    {
        return $this->salesStartsAt;
    }

    public function setSalesStartsAt(?\DateTimeInterface $salesStartsAt): static
    {
        $this->salesStartsAt = $salesStartsAt;

        return $this;
    }

    public function getSalesEndsAt(): ?\DateTimeInterface
    {
        return $this->salesEndsAt;
    }

    public function setSalesEndsAt(?\DateTimeInterface $salesEndsAt): static
    {
        $this->salesEndsAt = $salesEndsAt;

        return $this;
    }

    public function getAgeRestriction(): ?string
    {
        return $this->ageRestriction;
    }

    public function setAgeRestriction(?string $ageRestriction): static
    {
        $this->ageRestriction = $ageRestriction;

        return $this;
    }

    public function getLanguageCode(): string
    {
        return $this->languageCode;
    }

    public function setLanguageCode(string $languageCode): static
    {
        $this->languageCode = $languageCode;

        return $this;
    }

    public function isFeatured(): bool
    {
        return $this->isFeatured;
    }

    public function setIsFeatured(bool $isFeatured): static
    {
        $this->isFeatured = $isFeatured;

        return $this;
    }

    public function isHighlighted(): bool
    {
        return $this->isHighlighted;
    }

    public function setIsHighlighted(bool $isHighlighted): static
    {
        $this->isHighlighted = $isHighlighted;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeInterface $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getYoutubeUrl(): ?string
    {
        return $this->youtubeUrl;
    }

    public function setYoutubeUrl(?string $youtubeUrl): static
    {
        $this->youtubeUrl = $youtubeUrl;
        return $this;
    }

    public function getVenueNameText(): ?string
    {
        return $this->venueNameText;
    }

    public function setVenueNameText(?string $venueNameText): static
    {
        $this->venueNameText = $venueNameText;
        return $this;
    }

    public function getFullAddress(): ?string
    {
        return $this->fullAddress;
    }

    public function setFullAddress(?string $fullAddress): static
    {
        $this->fullAddress = $fullAddress;
        return $this;
    }

    public function isSinglePrice(): bool
    {
        return $this->singlePrice;
    }

    public function setSinglePrice(bool $singlePrice): static
    {
        $this->singlePrice = $singlePrice;
        return $this;
    }

    /**
     * @return \Doctrine\Common\Collections\Collection<int, EventTag>
     */
    public function getTags(): \Doctrine\Common\Collections\Collection
    {
        return $this->tags;
    }

    public function addTag(EventTag $tag): static
    {
        if (!$this->tags->contains($tag)) {
            $this->tags->add($tag);
        }
        return $this;
    }

    public function removeTag(EventTag $tag): static
    {
        $this->tags->removeElement($tag);
        return $this;
    }

    /**
     * @return \Doctrine\Common\Collections\Collection<int, EventLanguage>
     */
    public function getLanguages(): \Doctrine\Common\Collections\Collection
    {
        return $this->languages;
    }

    public function addLanguage(EventLanguage $language): static
    {
        if (!$this->languages->contains($language)) {
            $this->languages->add($language);
            $language->setEvent($this);
        }
        return $this;
    }

    public function removeLanguage(EventLanguage $language): static
    {
        if ($this->languages->removeElement($language)) {
            if ($language->getEvent() === $this) {
                $language->setEvent(null);
            }
        }
        return $this;
    }

    /**
     * @return \Doctrine\Common\Collections\Collection<int, EventAccessibility>
     */
    public function getAccessibilities(): \Doctrine\Common\Collections\Collection
    {
        return $this->accessibilities;
    }

    public function addAccessibility(EventAccessibility $accessibility): static
    {
        if (!$this->accessibilities->contains($accessibility)) {
            $this->accessibilities->add($accessibility);
            $accessibility->setEvent($this);
        }
        return $this;
    }

    public function removeAccessibility(EventAccessibility $accessibility): static
    {
        if ($this->accessibilities->removeElement($accessibility)) {
            if ($accessibility->getEvent() === $this) {
                $accessibility->setEvent(null);
            }
        }
        return $this;
    }

    /**
     * @return \Doctrine\Common\Collections\Collection<int, EventPaymentMethod>
     */
    public function getPaymentMethods(): \Doctrine\Common\Collections\Collection
    {
        return $this->paymentMethods;
    }

    public function addPaymentMethod(EventPaymentMethod $paymentMethod): static
    {
        if (!$this->paymentMethods->contains($paymentMethod)) {
            $this->paymentMethods->add($paymentMethod);
            $paymentMethod->setEvent($this);
        }
        return $this;
    }

    public function removePaymentMethod(EventPaymentMethod $paymentMethod): static
    {
        if ($this->paymentMethods->removeElement($paymentMethod)) {
            if ($paymentMethod->getEvent() === $this) {
                $paymentMethod->setEvent(null);
            }
        }
        return $this;
    }

    /**
     * @return \Doctrine\Common\Collections\Collection<int, EventMedia>
     */
    public function getMedia(): \Doctrine\Common\Collections\Collection
    {
        return $this->media;
    }

    public function addMedium(EventMedia $medium): static
    {
        if (!$this->media->contains($medium)) {
            $this->media->add($medium);
            $medium->setEvent($this);
        }
        return $this;
    }

    public function removeMedium(EventMedia $medium): static
    {
        if ($this->media->removeElement($medium)) {
            if ($medium->getEvent() === $this) {
                $medium->setEvent(null);
            }
        }
        return $this;
    }

    public function __toString(): string
    {
        return $this->title ?? '';
    }
}

