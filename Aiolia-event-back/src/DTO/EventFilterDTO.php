<?php

namespace App\DTO;

class EventFilterDTO
{
    private ?string $query = null;
    private ?int $categoryId = null;
    private ?\DateTimeInterface $startDate = null;
    private ?\DateTimeInterface $endDate = null;
    private ?string $location = null;
    private ?string $status = null;
    private ?float $minPrice = null;
    private ?float $maxPrice = null;
    private ?int $organizerId = null;
    private bool $featuredOnly = false;
    private int $page = 1;
    private int $limit = 20;
    private string $sortBy = 'startDate';
    private string $sortOrder = 'ASC';

    public function getQuery(): ?string
    {
        return $this->query;
    }

    public function setQuery(?string $query): self
    {
        $this->query = $query;
        return $this;
    }

    public function getCategoryId(): ?int
    {
        return $this->categoryId;
    }

    public function setCategoryId(?int $categoryId): self
    {
        $this->categoryId = $categoryId;
        return $this;
    }

    public function getStartDate(): ?\DateTimeInterface
    {
        return $this->startDate;
    }

    public function setStartDate(?\DateTimeInterface $startDate): self
    {
        $this->startDate = $startDate;
        return $this;
    }

    public function getEndDate(): ?\DateTimeInterface
    {
        return $this->endDate;
    }

    public function setEndDate(?\DateTimeInterface $endDate): self
    {
        $this->endDate = $endDate;
        return $this;
    }

    public function getLocation(): ?string
    {
        return $this->location;
    }

    public function setLocation(?string $location): self
    {
        $this->location = $location;
        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(?string $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function getMinPrice(): ?float
    {
        return $this->minPrice;
    }

    public function setMinPrice(?float $minPrice): self
    {
        $this->minPrice = $minPrice;
        return $this;
    }

    public function getMaxPrice(): ?float
    {
        return $this->maxPrice;
    }

    public function setMaxPrice(?float $maxPrice): self
    {
        $this->maxPrice = $maxPrice;
        return $this;
    }

    public function getOrganizerId(): ?int
    {
        return $this->organizerId;
    }

    public function setOrganizerId(?int $organizerId): self
    {
        $this->organizerId = $organizerId;
        return $this;
    }

    public function isFeaturedOnly(): bool
    {
        return $this->featuredOnly;
    }

    public function setFeaturedOnly(bool $featuredOnly): self
    {
        $this->featuredOnly = $featuredOnly;
        return $this;
    }

    public function getPage(): int
    {
        return $this->page;
    }

    public function setPage(int $page): self
    {
        $this->page = max(1, $page);
        return $this;
    }

    public function getLimit(): int
    {
        return $this->limit;
    }

    public function setLimit(int $limit): self
    {
        $this->limit = max(1, min(100, $limit));
        return $this;
    }

    public function getSortBy(): string
    {
        return $this->sortBy;
    }

    public function setSortBy(string $sortBy): self
    {
        $this->sortBy = $sortBy;
        return $this;
    }

    public function getSortOrder(): string
    {
        return $this->sortOrder;
    }

    public function setSortOrder(string $sortOrder): self
    {
        $this->sortOrder = in_array(strtoupper($sortOrder), ['ASC', 'DESC']) 
            ? strtoupper($sortOrder) 
            : 'ASC';
        return $this;
    }

    /**
     * Convertit le DTO en tableau de filtres
     */
    public function toArray(): array
    {
        return [
            'query' => $this->query,
            'category' => $this->categoryId,
            'startDate' => $this->startDate,
            'endDate' => $this->endDate,
            'location' => $this->location,
            'status' => $this->status,
            'minPrice' => $this->minPrice,
            'maxPrice' => $this->maxPrice,
            'organizerId' => $this->organizerId,
            'featuredOnly' => $this->featuredOnly,
        ];
    }

    /**
     * Crée un DTO depuis un tableau de requête
     */
    public static function fromArray(array $data): self
    {
        $dto = new self();

        if (isset($data['q'])) {
            $dto->setQuery($data['q']);
        }

        if (isset($data['category'])) {
            $dto->setCategoryId((int) $data['category']);
        }

        if (isset($data['startDate'])) {
            $dto->setStartDate(new \DateTime($data['startDate']));
        }

        if (isset($data['endDate'])) {
            $dto->setEndDate(new \DateTime($data['endDate']));
        }

        if (isset($data['location'])) {
            $dto->setLocation($data['location']);
        }

        if (isset($data['status'])) {
            $dto->setStatus($data['status']);
        }

        if (isset($data['minPrice'])) {
            $dto->setMinPrice((float) $data['minPrice']);
        }

        if (isset($data['maxPrice'])) {
            $dto->setMaxPrice((float) $data['maxPrice']);
        }

        if (isset($data['organizerId'])) {
            $dto->setOrganizerId((int) $data['organizerId']);
        }

        if (isset($data['featured'])) {
            $dto->setFeaturedOnly((bool) $data['featured']);
        }

        if (isset($data['page'])) {
            $dto->setPage((int) $data['page']);
        }

        if (isset($data['limit'])) {
            $dto->setLimit((int) $data['limit']);
        }

        if (isset($data['sortBy'])) {
            $dto->setSortBy($data['sortBy']);
        }

        if (isset($data['sortOrder'])) {
            $dto->setSortOrder($data['sortOrder']);
        }

        return $dto;
    }
}

