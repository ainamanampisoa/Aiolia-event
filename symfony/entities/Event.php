<?php

namespace App\Entity;

use App\Repository\EventRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Gedmo\Mapping\Annotation as Gedmo;

#[ORM\Entity(repositoryClass: EventRepository::class)]
#[ORM\Table(name: 'events')]
#[ORM\HasLifecycleCallbacks]
#[ORM\Index(name: 'idx_organizer', columns: ['organizer_id'])]
#[ORM\Index(name: 'idx_category', columns: ['category_id'])]
#[ORM\Index(name: 'idx_status', columns: ['status'])]
#[ORM\Index(name: 'idx_dates', columns: ['start_date', 'end_date'])]
#[ORM\Index(name: 'idx_slug', columns: ['slug'])]
class Event
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::BIGINT)]
    #[Groups(['event:read', 'ticket:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'organizedEvents')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['event:read'])]
    private ?User $organizer = null;

    #[ORM\ManyToOne(targetEntity: EventCategory::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['event:read'])]
    private ?EventCategory $category = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le titre est obligatoire')]
    #[Assert\Length(max: 255)]
    #[Groups(['event:read', 'event:write'])]
    private ?string $title = null;

    #[ORM\Column(length: 255, unique: true)]
    #[Gedmo\Slug(fields: ['title'])]
    #[Groups(['event:read'])]
    private ?string $slug = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['event:read', 'event:write'])]
    private ?string $description = null;

    #[ORM\Column(length: 500, nullable: true)]
    #[Assert\Length(max: 500)]
    #[Groups(['event:read', 'event:write'])]
    private ?string $shortDescription = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Length(max: 255)]
    #[Groups(['event:read', 'event:write'])]
    private ?string $location = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['event:read', 'event:write'])]
    private ?string $address = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 8, nullable: true)]
    #[Groups(['event:read', 'event:write'])]
    private ?string $latitude = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 11, scale: 8, nullable: true)]
    #[Groups(['event:read', 'event:write'])]
    private ?string $longitude = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Assert\NotBlank(message: 'La date de début est obligatoire')]
    #[Groups(['event:read', 'event:write'])]
    private ?\DateTimeInterface $startDate = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Assert\NotBlank(message: 'La date de fin est obligatoire')]
    #[Assert\GreaterThan(propertyPath: 'startDate', message: 'La date de fin doit être après la date de début')]
    #[Groups(['event:read', 'event:write'])]
    private ?\DateTimeInterface $endDate = null;

    #[ORM\Column(length: 50, options: ['default' => 'Indian/Antananarivo'])]
    #[Groups(['event:read', 'event:write'])]
    private string $timezone = 'Indian/Antananarivo';

    #[ORM\Column(length: 20, enumType: EventStatus::class, options: ['default' => 'draft'])]
    #[Groups(['event:read'])]
    private EventStatus $status = EventStatus::DRAFT;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    #[Groups(['event:read'])]
    private bool $isFeatured = false;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    #[Groups(['event:read'])]
    private bool $isPremium = false;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $premiumExpiresAt = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    #[Groups(['event:read', 'event:write'])]
    private ?int $totalCapacity = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    #[Groups(['event:read', 'event:write'])]
    private ?int $minAge = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    #[Groups(['event:read', 'event:write'])]
    private ?int $maxAge = null;

    #[ORM\Column(type: Types::INTEGER, options: ['default' => 0])]
    #[Groups(['event:read'])]
    private int $viewsCount = 0;

    #[ORM\Column(type: Types::INTEGER, options: ['default' => 0])]
    #[Groups(['event:read'])]
    private int $favoritesCount = 0;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Groups(['event:read'])]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Groups(['event:read'])]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Groups(['event:read'])]
    private ?\DateTimeInterface $publishedAt = null;

    // Relations

    #[ORM\OneToMany(mappedBy: 'event', targetEntity: TicketCategory::class, cascade: ['persist', 'remove'])]
    #[Groups(['event:read'])]
    private Collection $ticketCategories;

    #[ORM\OneToMany(mappedBy: 'event', targetEntity: EventMedia::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[Groups(['event:read'])]
    private Collection $media;

    #[ORM\OneToMany(mappedBy: 'event', targetEntity: EventTeam::class, cascade: ['persist', 'remove'])]
    private Collection $team;

    #[ORM\OneToMany(mappedBy: 'event', targetEntity: Review::class, cascade: ['persist', 'remove'])]
    private Collection $reviews;

    #[ORM\OneToMany(mappedBy: 'event', targetEntity: Favorite::class, cascade: ['persist', 'remove'])]
    private Collection $favorites;

    #[ORM\OneToOne(mappedBy: 'event', targetEntity: EventStatistics::class, cascade: ['persist', 'remove'])]
    #[Groups(['event:read'])]
    private ?EventStatistics $statistics = null;

    public function __construct()
    {
        $this->ticketCategories = new ArrayCollection();
        $this->media = new ArrayCollection();
        $this->team = new ArrayCollection();
        $this->reviews = new ArrayCollection();
        $this->favorites = new ArrayCollection();
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new \DateTime();
    }

    // Getters & Setters

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOrganizer(): ?User
    {
        return $this->organizer;
    }

    public function setOrganizer(?User $organizer): static
    {
        $this->organizer = $organizer;
        return $this;
    }

    public function getCategory(): ?EventCategory
    {
        return $this->category;
    }

    public function setCategory(?EventCategory $category): static
    {
        $this->category = $category;
        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;
        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): static
    {
        $this->slug = $slug;
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

    public function getShortDescription(): ?string
    {
        return $this->shortDescription;
    }

    public function setShortDescription(?string $shortDescription): static
    {
        $this->shortDescription = $shortDescription;
        return $this;
    }

    public function getLocation(): ?string
    {
        return $this->location;
    }

    public function setLocation(?string $location): static
    {
        $this->location = $location;
        return $this;
    }

    public function getStartDate(): ?\DateTimeInterface
    {
        return $this->startDate;
    }

    public function setStartDate(\DateTimeInterface $startDate): static
    {
        $this->startDate = $startDate;
        return $this;
    }

    public function getEndDate(): ?\DateTimeInterface
    {
        return $this->endDate;
    }

    public function setEndDate(\DateTimeInterface $endDate): static
    {
        $this->endDate = $endDate;
        return $this;
    }

    public function getStatus(): EventStatus
    {
        return $this->status;
    }

    public function setStatus(EventStatus $status): static
    {
        $this->status = $status;
        if ($status === EventStatus::PUBLISHED && $this->publishedAt === null) {
            $this->publishedAt = new \DateTime();
        }
        return $this;
    }

    public function publish(): static
    {
        $this->status = EventStatus::PUBLISHED;
        $this->publishedAt = new \DateTime();
        return $this;
    }

    public function cancel(): static
    {
        $this->status = EventStatus::CANCELLED;
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

    public function isPremium(): bool
    {
        return $this->isPremium;
    }

    public function getViewsCount(): int
    {
        return $this->viewsCount;
    }

    public function incrementViews(): static
    {
        $this->viewsCount++;
        return $this;
    }

    public function getFavoritesCount(): int
    {
        return $this->favoritesCount;
    }

    public function incrementFavorites(): static
    {
        $this->favoritesCount++;
        return $this;
    }

    public function decrementFavorites(): static
    {
        if ($this->favoritesCount > 0) {
            $this->favoritesCount--;
        }
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getPublishedAt(): ?\DateTimeInterface
    {
        return $this->publishedAt;
    }

    // Relations

    /**
     * @return Collection<int, TicketCategory>
     */
    public function getTicketCategories(): Collection
    {
        return $this->ticketCategories;
    }

    public function addTicketCategory(TicketCategory $ticketCategory): static
    {
        if (!$this->ticketCategories->contains($ticketCategory)) {
            $this->ticketCategories->add($ticketCategory);
            $ticketCategory->setEvent($this);
        }
        return $this;
    }

    public function getAvailableTickets(): int
    {
        $available = 0;
        foreach ($this->ticketCategories as $category) {
            $available += $category->getAvailableQuantity();
        }
        return $available;
    }

    public function getTotalTickets(): int
    {
        $total = 0;
        foreach ($this->ticketCategories as $category) {
            $total += $category->getQuantityTotal();
        }
        return $total;
    }

    public function getSoldTickets(): int
    {
        $sold = 0;
        foreach ($this->ticketCategories as $category) {
            $sold += $category->getQuantitySold();
        }
        return $sold;
    }

    public function getMinPrice(): ?float
    {
        $prices = [];
        foreach ($this->ticketCategories as $category) {
            if ($category->isActive()) {
                $prices[] = $category->getPrice();
            }
        }
        return !empty($prices) ? min($prices) : null;
    }

    public function getMaxPrice(): ?float
    {
        $prices = [];
        foreach ($this->ticketCategories as $category) {
            if ($category->isActive()) {
                $prices[] = $category->getPrice();
            }
        }
        return !empty($prices) ? max($prices) : null;
    }

    /**
     * @return Collection<int, EventMedia>
     */
    public function getMedia(): Collection
    {
        return $this->media;
    }

    public function addMedia(EventMedia $media): static
    {
        if (!$this->media->contains($media)) {
            $this->media->add($media);
            $media->setEvent($this);
        }
        return $this;
    }

    public function getPrimaryImage(): ?EventMedia
    {
        foreach ($this->media as $media) {
            if ($media->isPrimary() && $media->getMediaType() === MediaType::IMAGE) {
                return $media;
            }
        }
        return $this->media->first() ?: null;
    }

    /**
     * @return Collection<int, EventTeam>
     */
    public function getTeam(): Collection
    {
        return $this->team;
    }

    /**
     * @return Collection<int, Review>
     */
    public function getReviews(): Collection
    {
        return $this->reviews;
    }

    public function getAverageRating(): float
    {
        if ($this->statistics) {
            return $this->statistics->getAverageRating();
        }
        return 0.0;
    }

    public function getStatistics(): ?EventStatistics
    {
        return $this->statistics;
    }

    public function setStatistics(?EventStatistics $statistics): static
    {
        // unset the owning side of the relation if necessary
        if ($statistics === null && $this->statistics !== null) {
            $this->statistics->setEvent(null);
        }

        // set the owning side of the relation if necessary
        if ($statistics !== null && $statistics->getEvent() !== $this) {
            $statistics->setEvent($this);
        }

        $this->statistics = $statistics;
        return $this;
    }

    public function __toString(): string
    {
        return $this->title;
    }
}

enum EventStatus: string
{
    case DRAFT = 'draft';
    case PUBLISHED = 'published';
    case ONGOING = 'ongoing';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';
}

enum MediaType: string
{
    case IMAGE = 'image';
    case VIDEO = 'video';
    case DOCUMENT = 'document';
}

