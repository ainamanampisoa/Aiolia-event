<?php

namespace App\Entity;

use App\Repository\Organisateur\EventMediaRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EventMediaRepository::class)]
#[ORM\Table(name: 'medias_evenements', schema: 'aiolia')]
#[ORM\HasLifecycleCallbacks]
class EventMedia
{
    public const TYPE_IMAGE = 'image';
    public const TYPE_VIDEO = 'video';
    public const TYPE_DOCUMENT = 'document';

    public const FORMAT_PORTRAIT = 'portrait';
    public const FORMAT_PAYSAGE = 'paysage';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::BIGINT)]
    private ?string $id = null;

    #[ORM\ManyToOne(targetEntity: Event::class, inversedBy: 'media')]
    #[ORM\JoinColumn(name: 'id_evenement', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?Event $event = null;

    #[ORM\Column(name: 'type_media', type: Types::STRING, length: 20)]
    private string $mediaType;

    #[ORM\Column(name: 'url', type: Types::TEXT)]
    private string $url;

    #[ORM\Column(name: 'texte_alternatif', type: Types::TEXT, nullable: true)]
    private ?string $altText = null;

    #[ORM\Column(name: 'ordre_affichage', type: Types::INTEGER, options: ['default' => 0])]
    private int $displayOrder = 0;

    #[ORM\Column(name: 'est_public', type: Types::BOOLEAN, options: ['default' => true])]
    private bool $isPublic = true;

    #[ORM\Column(name: 'format_affiche', type: Types::STRING, length: 20, nullable: true)]
    private ?string $posterFormat = null;

    #[ORM\Column(name: 'est_affiche_principale', type: Types::BOOLEAN, options: ['default' => false])]
    private bool $isMainPoster = false;

    #[ORM\Column(name: 'cree_le', type: Types::DATETIMETZ_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\PrePersist]
    public function initializeCreatedAt(): void
    {
        $this->createdAt ??= new \DateTimeImmutable();
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getEvent(): ?Event
    {
        return $this->event;
    }

    public function setEvent(?Event $event): static
    {
        $this->event = $event;

        return $this;
    }

    public function getMediaType(): string
    {
        return $this->mediaType;
    }

    public function setMediaType(string $mediaType): static
    {
        $this->mediaType = $mediaType;

        return $this;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function setUrl(string $url): static
    {
        $this->url = $url;

        return $this;
    }

    public function getAltText(): ?string
    {
        return $this->altText;
    }

    public function setAltText(?string $altText): static
    {
        $this->altText = $altText;

        return $this;
    }

    public function getDisplayOrder(): int
    {
        return $this->displayOrder;
    }

    public function setDisplayOrder(int $displayOrder): static
    {
        $this->displayOrder = $displayOrder;

        return $this;
    }

    public function isPublic(): bool
    {
        return $this->isPublic;
    }

    public function setIsPublic(bool $isPublic): static
    {
        $this->isPublic = $isPublic;

        return $this;
    }

    public function getPosterFormat(): ?string
    {
        return $this->posterFormat;
    }

    public function setPosterFormat(?string $posterFormat): static
    {
        if ($posterFormat !== null && !in_array($posterFormat, [self::FORMAT_PORTRAIT, self::FORMAT_PAYSAGE])) {
            throw new \InvalidArgumentException("Format d'affiche invalide: {$posterFormat}");
        }
        $this->posterFormat = $posterFormat;
        return $this;
    }

    public function isMainPoster(): bool
    {
        return $this->isMainPoster;
    }

    public function setIsMainPoster(bool $isMainPoster): static
    {
        $this->isMainPoster = $isMainPoster;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }
}

