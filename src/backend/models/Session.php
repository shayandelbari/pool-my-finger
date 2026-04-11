<?php

class Session
{
    private int $id;
    private User $user;
    private string $token;
    private DateTime $createdAt;
    private DateTime $expires;
    private bool $revoked;

    public function __construct(int $id, User $user, string $token, DateTime $createdAt, DateTime $expires, bool $revoked)
    {
        $this->id = $id;
        $this->user = $user;
        $this->token = $token;
        $this->createdAt = $createdAt;
        $this->expires = $expires;
        $this->revoked = $revoked;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getToken(): string
    {
        return $this->token;
    }

    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    public function getExpires(): DateTime
    {
        return $this->expires;
    }

    public function isRevoked(): bool
    {
        return $this->revoked;
    }
}