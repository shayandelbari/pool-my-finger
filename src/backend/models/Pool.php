<?php

class Pool
{
    private int $id;
    private string $name;
    private ?string $address;
    private ?string $imageUrl;
    private ?string $website;
    private ?string $map;
    private ?float $latitude;
    private ?float $longitude;
    private ?string $phone;
    private bool $active;
    private DateTime $createdAt;
    /**
     * @var PoolType[]
     */
    private array $types;

    public function __construct(int $id, string $name, ?string $address, ?string $imageUrl, ?string $website, ?string $map, ?float $latitude, ?float $longitude, ?string $phone, bool $active, DateTime $createdAt, array $types = [])
    {
        $this->id = $id;
        $this->name = $name;
        $this->address = $address;
        $this->imageUrl = $imageUrl;
        $this->website = $website;
        $this->map = $map;
        $this->latitude = $latitude;
        $this->longitude = $longitude;
        $this->phone = $phone;
        $this->active = $active;
        $this->createdAt = $createdAt;
        $this->types = $types;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function getImageUrl(): ?string
    {
        return $this->imageUrl;
    }

    public function getWebsite(): ?string
    {
        return $this->website;
    }

    public function getMap(): ?string
    {
        return $this->map;
    }

    public function getLatitude(): ?float
    {
        return $this->latitude;
    }

    public function getLongitude(): ?float
    {
        return $this->longitude;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    /**
     * @return PoolType[]
     */
    public function getTypes(): array
    {
        return $this->types;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }
}