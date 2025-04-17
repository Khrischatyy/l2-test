<?php

namespace App\Entity;

use App\Repository\LeadRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: LeadRepository::class)]
#[ORM\Table(name: 'leads', options: ['row_format' => 'COMPRESSED'])]
#[ORM\Index(name: 'IDX_leads_search', columns: ['first_name', 'last_name', 'created_at'])]
#[ORM\Index(name: 'IDX_leads_created', columns: ['created_at'])]
#[ORM\Index(name: 'IDX_leads_created_request', columns: ['created_by_request_id'])]
#[ORM\Index(name: 'IDX_leads_modified_request', columns: ['last_modified_by_request_id'])]
#[ORM\UniqueConstraint(name: 'UNIQ_4C94A832E7927C74', columns: ['email'])]
class Lead implements \JsonSerializable
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['lead:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['lead:read'])]
    private ?string $firstName = null;

    #[ORM\Column(length: 255)]
    #[Groups(['lead:read'])]
    private ?string $lastName = null;

    #[ORM\Column(length: 255)]
    #[Groups(['lead:read'])]
    private ?string $email = null;

    #[ORM\Column(length: 20)]
    #[Groups(['lead:read'])]
    private ?string $phone = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Groups(['lead:read'])]
    private ?\DateTimeInterface $dateOfBirth = null;

    #[ORM\Column(type: Types::JSON)]
    #[Groups(['lead:read'])]
    private array $additionalData = [];

    #[ORM\Column]
    #[Groups(['lead:read'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    #[Groups(['lead:read'])]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\ManyToOne(targetEntity: ApiRequest::class)]
    #[ORM\JoinColumn(name: 'created_by_request_id', referencedColumnName: 'id')]
    private ?ApiRequest $createdByRequest = null;

    #[ORM\ManyToOne(targetEntity: ApiRequest::class)]
    #[ORM\JoinColumn(name: 'last_modified_by_request_id', referencedColumnName: 'id')]
    private ?ApiRequest $lastModifiedByRequest = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): self
    {
        $this->firstName = $firstName;
        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): self
    {
        $this->lastName = $lastName;
        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;
        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(string $phone): self
    {
        $this->phone = $phone;
        return $this;
    }

    public function getDateOfBirth(): ?\DateTimeInterface
    {
        return $this->dateOfBirth;
    }

    public function setDateOfBirth(\DateTimeInterface $dateOfBirth): self
    {
        $this->dateOfBirth = $dateOfBirth;
        return $this;
    }

    public function getAdditionalData(): array
    {
        return $this->additionalData;
    }

    public function setAdditionalData(array $additionalData): self
    {
        $this->additionalData = $additionalData;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): self
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function getCreatedByRequest(): ?ApiRequest
    {
        return $this->createdByRequest;
    }

    public function setCreatedByRequest(?ApiRequest $createdByRequest): self
    {
        $this->createdByRequest = $createdByRequest;
        return $this;
    }

    public function getLastModifiedByRequest(): ?ApiRequest
    {
        return $this->lastModifiedByRequest;
    }

    public function setLastModifiedByRequest(?ApiRequest $lastModifiedByRequest): self
    {
        $this->lastModifiedByRequest = $lastModifiedByRequest;
        return $this;
    }

    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'firstName' => $this->firstName,
            'lastName' => $this->lastName,
            'email' => $this->email,
            'phone' => $this->phone,
            'dateOfBirth' => $this->dateOfBirth?->format('Y-m-d'),
            'additionalData' => $this->additionalData,
            'createdAt' => $this->createdAt?->format('Y-m-d\TH:i:s.u\Z'),
            'updatedAt' => $this->updatedAt?->format('Y-m-d\TH:i:s.u\Z')
        ];
    }
} 