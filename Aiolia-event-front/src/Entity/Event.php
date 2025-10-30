<?php

namespace App\Entity;

use App\Repository\EventRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: EventRepository::class)]
#[ORM\Table(name: 'events')]
class Event
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 3, max: 255)]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank]
    private ?string $description = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $shortDescription = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Assert\NotBlank]
    #[Assert\GreaterThan('now')]
    private ?\DateTimeInterface $startDate = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Assert\NotBlank]
    #[Assert\GreaterThan(propertyPath: 'startDate')]
    private ?\DateTimeInterface $endDate = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    private ?string $location = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank]
    private ?string $city = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank]
    private ?string $country = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank]
    private ?string $category = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $imageUrl = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $images = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $videoUrl = null;

    #[ORM\Column]
    private int $maxCapacity = 0;

    #[ORM\Column]
    private int $currentBookings = 0;

    #[ORM\Column]
    private bool $isActive = true;

    #[ORM\Column]
    private bool $isPublished = false;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\ManyToOne(inversedBy: 'organizedEvents')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $organizer = null;

    #[ORM\OneToMany(targetEntity: TicketCategory::class, mappedBy: 'event', cascade: ['persist', 'remove'])]
    private Collection $ticketCategories;

    #[ORM\OneToMany(targetEntity: Ticket::class, mappedBy: 'event')]
    private Collection $tickets;

    #[ORM\OneToMany(targetEntity: EventFavorite::class, mappedBy: 'event')]
    private Collection $favorites;

    #[ORM\OneToMany(targetEntity: Promotion::class, mappedBy: 'event')]
    private Collection $promotions;

    public function __construct()
    {
        $this->ticketCategories = new ArrayCollection();
        $this->tickets = new ArrayCollection();
        $this->favorites = new ArrayCollection();
        $this->promotions = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
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

    public function getLocation(): ?string
    {
        return $this->location;
    }

    public function setLocation(string $location): static
    {
        $this->location = $location;
        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(string $city): static
    {
        $this->city = $city;
        return $this;
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function setCountry(string $country): static
    {
        $this->country = $country;
        return $this;
    }

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function setCategory(string $category): static
    {
        $this->category = $category;
        return $this;
    }

    public function getImageUrl(): ?string
    {
        return $this->imageUrl;
    }

    public function setImageUrl(?string $imageUrl): static
    {
        $this->imageUrl = $imageUrl;
        return $this;
    }

    public function getImages(): ?array
    {
        return $this->images;
    }

    public function setImages(?array $images): static
    {
        $this->images = $images;
        return $this;
    }

    public function getVideoUrl(): ?string
    {
        return $this->videoUrl;
    }

    public function setVideoUrl(?string $videoUrl): static
    {
        $this->videoUrl = $videoUrl;
        return $this;
    }

    public function getMaxCapacity(): int
    {
        return $this->maxCapacity;
    }

    public function setMaxCapacity(int $maxCapacity): static
    {
        $this->maxCapacity = $maxCapacity;
        return $this;
    }

    public function getCurrentBookings(): int
    {
        return $this->currentBookings;
    }

    public function setCurrentBookings(int $currentBookings): static
    {
        $this->currentBookings = $currentBookings;
        return $this;
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

    public function isPublished(): bool
    {
        return $this->isPublished;
    }

    public function setIsPublished(bool $isPublished): static
    {
        $this->isPublished = $isPublished;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
        return $this;
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

    public function removeTicketCategory(TicketCategory $ticketCategory): static
    {
        if ($this->ticketCategories->removeElement($ticketCategory)) {
            // set the owning side to null (unless already changed)
            if ($ticketCategory->getEvent() === $this) {
                $ticketCategory->setEvent(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Ticket>
     */
    public function getTickets(): Collection
    {
        return $this->tickets;
    }

    public function addTicket(Ticket $ticket): static
    {
        if (!$this->tickets->contains($ticket)) {
            $this->tickets->add($ticket);
            $ticket->setEvent($this);
        }

        return $this;
    }

    public function removeTicket(Ticket $ticket): static
    {
        if ($this->tickets->removeElement($ticket)) {
            // set the owning side to null (unless already changed)
            if ($ticket->getEvent() === $this) {
                $ticket->setEvent(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, EventFavorite>
     */
    public function getFavorites(): Collection
    {
        return $this->favorites;
    }

    public function addFavorite(EventFavorite $favorite): static
    {
        if (!$this->favorites->contains($favorite)) {
            $this->favorites->add($favorite);
            $favorite->setEvent($this);
        }

        return $this;
    }

    public function removeFavorite(EventFavorite $favorite): static
    {
        if ($this->favorites->removeElement($favorite)) {
            // set the owning side to null (unless already changed)
            if ($favorite->getEvent() === $this) {
                $favorite->setEvent(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Promotion>
     */
    public function getPromotions(): Collection
    {
        return $this->promotions;
    }

    public function addPromotion(Promotion $promotion): static
    {
        if (!$this->promotions->contains($promotion)) {
            $this->promotions->add($promotion);
            $promotion->setEvent($this);
        }

        return $this;
    }

    public function removePromotion(Promotion $promotion): static
    {
        if ($this->promotions->removeElement($promotion)) {
            // set the owning side to null (unless already changed)
            if ($promotion->getEvent() === $this) {
                $promotion->setEvent(null);
            }
        }

        return $this;
    }

    public function getAvailableCapacity(): int
    {
        return $this->maxCapacity - $this->currentBookings;
    }

    public function isFullyBooked(): bool
    {
        return $this->currentBookings >= $this->maxCapacity;
    }

    public function getDuration(): int
    {
        return $this->endDate->getTimestamp() - $this->startDate->getTimestamp();
    }

    public function isUpcoming(): bool
    {
        return $this->startDate > new \DateTime();
    }

    public function isPast(): bool
    {
        return $this->endDate < new \DateTime();
    }
}

