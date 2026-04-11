<?php

class User
{
    private int $id;
    private string $username;
    private string $passwordHash;
    private DateTime $createdAt;

    public function __construct(int $id, string $username, string $passwordHash, DateTime $createdAt)
    {
        $this->id = $id;
        $this->username = $username;
        $this->passwordHash = $passwordHash;
        $this->createdAt = $createdAt;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function getPasswordHash(): string
    {
        return $this->passwordHash;
    }

    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }
}