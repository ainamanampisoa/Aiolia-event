<?php

namespace App\Entity;

use App\Repository\UserValidationRequestRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserValidationRequestRepository::class)]
#[ORM\Table(name: 'user_validation_requests', schema: 'aiolia')]
#[ORM\HasLifecycleCallbacks]
class UserValidationRequest
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_CANCELLED = 'cancelled';

    #[ORM\Id]
    #[ORM\Column(type: Types::GUID, unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'App\Doctrine\UuidV4Generator')]
    private ?string $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\Column(name: 'requested_at', type: Types::DATETIMETZ_MUTABLE)]
    private ?\DateTimeInterface $requestedAt = null;

    #[ORM\Column(type: Types::STRING, length: 20, options: ['default' => self::STATUS_PENDING])]
    private string $status = self::STATUS_PENDING;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'reviewer_user_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?User $reviewer = null;

    #[ORM\Column(name: 'reviewed_at', type: Types::DATETIMETZ_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $reviewedAt = null;

    #[ORM\Column(name: 'rejection_reason', type: Types::TEXT, nullable: true)]
    private ?string $rejectionReason = null;

    #[ORM\Column(name: 'additional_documents', type: Types::JSON, nullable: true)]
    private ?array $additionalDocuments = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $metadata = null;

    #[ORM\PrePersist]
    public function initializeRequestedAt(): void
    {
        $this->requestedAt ??= new \DateTimeImmutable();
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getRequestedAt(): ?\DateTimeInterface
    {
        return $this->requestedAt;
    }

    public function setRequestedAt(\DateTimeInterface $requestedAt): static
    {
        $this->requestedAt = $requestedAt;

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

    public function getReviewer(): ?User
    {
        return $this->reviewer;
    }

    public function setReviewer(?User $reviewer): static
    {
        $this->reviewer = $reviewer;

        return $this;
    }

    public function getReviewedAt(): ?\DateTimeInterface
    {
        return $this->reviewedAt;
    }

    public function setReviewedAt(?\DateTimeInterface $reviewedAt): static
    {
        $this->reviewedAt = $reviewedAt;

        return $this;
    }

    public function getRejectionReason(): ?string
    {
        return $this->rejectionReason;
    }

    public function setRejectionReason(?string $rejectionReason): static
    {
        $this->rejectionReason = $rejectionReason;

        return $this;
    }

    public function getAdditionalDocuments(): ?array
    {
        return $this->additionalDocuments;
    }

    public function setAdditionalDocuments(?array $additionalDocuments): static
    {
        $this->additionalDocuments = $additionalDocuments;

        return $this;
    }

    public function getMetadata(): ?array
    {
        return $this->metadata;
    }

    public function setMetadata(?array $metadata): static
    {
        $this->metadata = $metadata;

        return $this;
    }
}

