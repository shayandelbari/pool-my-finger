<?php

class Pool
{
    private int $id;
    private string $name;
    private string $address;
    private string $imageUrl;
    private string $website;
    private string $map;
    private float $lattitude;
    private float $longitude;
    private float $phone;
    private string $type;
    private bool $active;
    private DateTime $createdAt;

    public function __construct(int $id, string $name, string $address, string $imageUrl, string $website, string $map, float $lattitude, float $longitude, float $phone, string $type, bool $active, DateTime $createdAt)
    {
        $this->id = $id;
        $this->name = $name;
        $this->address = $address;
        $this->imageUrl = $imageUrl;
        $this->website = $website;
        $this->map = $map;
        $this->lattitude = $lattitude;
        $this->longitude = $longitude;
        $this->phone = $phone;
        $this->type = $type;
        $this->active = $active;
        $this->createdAt = $createdAt;
    }
}