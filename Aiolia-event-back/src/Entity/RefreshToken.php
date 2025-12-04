<?php

namespace App\Entity;

use App\Repository\RefreshTokenRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RefreshTokenRepository::class)]
#[ORM\Table(name: 'refresh_tokens', schema: 'aiolia', uniqueConstraints: [
    new ORM\UniqueConstraint(name: 'uq_refresh_tokens_token_hash', columns: ['token_hash'])
], indexes: [
    new ORM\Index(name: 'idx_refresh_tokens_user', columns: ['user_id']),
    new ORM\Index(name: 'idx_refresh_tokens_active', columns: ['user_id', 'expires_at'], options: ['where' => 'revoked_at IS NULL'])
])]
class RefreshToken
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::BIGINT)]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\Column(name: 'token_hash', type: Types::TEXT)]
    private string $tokenHash;

    #[ORM\Column(name: 'session_id', type: Types::GUID, nullable: true)]
    private ?string $sessionId = null;

    #[ORM\Column(name: 'user_agent', type: Types::TEXT, nullable: true)]
    private ?string $userAgent = null;

    #[ORM\Column(name: 'ip_address', type: Types::STRING, length: 45, nullable: true, options: ['comment' => 'IPv4/IPv6 address'])]
    private ?string $ipAddress = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $metadata = null;

    #[ORM\Column(name: 'issued_at', type: Types::DATETIMETZ_IMMUTABLE)]
    private \DateTimeImmutable $issuedAt;

    #[ORM\Column(name: 'expires_at', type: Types::DATETIMETZ_IMMUTABLE)]
    private \DateTimeImmutable $expiresAt;

    #[ORM\Column(name: 'revoked_at', type: Types::DATETIMETZ_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $revokedAt = null;

    #[ORM\ManyToOne(targetEntity: self::class)]
    #[ORM\JoinColumn(name: 'replaced_by_token', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?self $replacedByToken = null;

    public function __construct()
    {
        $this->issuedAt = new \DateTimeImmutable();
        $this->expiresAt = $this->issuedAt->modify('+30 days');
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getTokenHash(): string
    {
        return $this->tokenHash;
    }

    public function setTokenHash(string $tokenHash): self
    {
        $this->tokenHash = $tokenHash;

        return $this;
    }

    public function getSessionId(): ?string
    {
        return $this->sessionId;
    }

    public function setSessionId(?string $sessionId): self
    {
        $this->sessionId = $sessionId;

        return $this;
    }

    public function getUserAgent(): ?string
    {
        return $this->userAgent;
    }

    public function setUserAgent(?string $userAgent): self
    {
        $this->userAgent = $userAgent;

        return $this;
    }

    public function getIpAddress(): ?string
    {
        return $this->ipAddress;
    }

    public function setIpAddress(?string $ipAddress): self
    {
        $this->ipAddress = $ipAddress;

        return $this;
    }

    public function getMetadata(): ?array
    {
        return $this->metadata;
    }

    public function setMetadata(?array $metadata): self
    {
        $this->metadata = $metadata;

        return $this;
    }

    public function getIssuedAt(): \DateTimeImmutable
    {
        return $this->issuedAt;
    }

    public function setIssuedAt(\DateTimeImmutable $issuedAt): self
    {
        $this->issuedAt = $issuedAt;

        return $this;
    }

    public function getExpiresAt(): \DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function setExpiresAt(\DateTimeImmutable $expiresAt): self
    {
        $this->expiresAt = $expiresAt;

        return $this;
    }

    public function getRevokedAt(): ?\DateTimeImmutable
    {
        return $this->revokedAt;
    }

    public function setRevokedAt(?\DateTimeImmutable $revokedAt): self
    {
        $this->revokedAt = $revokedAt;

        return $this;
    }

    public function getReplacedByToken(): ?self
    {
        return $this->replacedByToken;
    }

    public function setReplacedByToken(?self $replacedByToken): self
    {
        $this->replacedByToken = $replacedByToken;

        return $this;
    }

    public function isExpired(\DateTimeImmutable $referenceDate = new \DateTimeImmutable()): bool
    {
        return $this->expiresAt <= $referenceDate;
    }

    public function isRevoked(): bool
    {
        return $this->revokedAt !== null;
    }

    public function isRotated(): bool
    {
        return $this->replacedByToken !== null;
    }

    public function isUsable(\DateTimeImmutable $referenceDate = new \DateTimeImmutable()): bool
    {
        return !$this->isRevoked() && !$this->isExpired($referenceDate) && !$this->isRotated();
    }

    public function markRevoked(?\DateTimeImmutable $at = null): void
    {
        if ($this->revokedAt !== null) {
            return;
        }

        $this->revokedAt = $at ?? new \DateTimeImmutable();
    }
}


