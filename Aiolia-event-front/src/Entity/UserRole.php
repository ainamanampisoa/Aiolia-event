<?php

namespace App\Entity;

use App\Repository\UserRoleRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: UserRoleRepository::class)]
#[ORM\Table(name: 'user_roles', schema: 'aiolia')]
class UserRole
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    private ?Uuid $id = null;

    #[ORM\Column(name: 'code', type: 'string', length: 100, unique: true)]
    private string $code;

    #[ORM\Column(name: 'label', type: 'string', length: 150)]
    private string $label;

    #[ORM\Column(name: 'description', type: 'text', nullable: true)]
    private ?string $description = null;

    /**
     * @var Collection<int, UserRoleAssignment>
     */
    #[ORM\OneToMany(mappedBy: 'role', targetEntity: UserRoleAssignment::class, orphanRemoval: true)]
    private Collection $assignments;

    public function __construct()
    {
        $this->assignments = new ArrayCollection();
    }

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getIdAsString(): ?string
    {
        return $this->id?->toRfc4122();
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $code): self
    {
        $this->code = strtoupper($code);

        return $this;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function setLabel(string $label): self
    {
        $this->label = $label;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return Collection<int, UserRoleAssignment>
     */
    public function getAssignments(): Collection
    {
        return $this->assignments;
    }
}
