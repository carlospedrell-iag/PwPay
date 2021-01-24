<?php

declare(strict_types=1);

namespace Pw\SlimApp\Model;

use DateTime;

final class User
{
    private int $id;
    private string $email;
    private string $password;
    private DateTime $birthdate;
    private string $phone;
    private DateTime $createdAt;
    private DateTime $updatedAt;
    private string $auth_token;
    private bool $is_activated;
    private float $balance;

    public function __construct(
        string $email,
        string $password,
        DateTime $birthdate,
        string $phone,
        DateTime $createdAt,
        DateTime $updatedAt,
        string $auth_token,
        bool $is_activated,
        float $balance,
        string $owner_name,
        string $iban
    ) {
        $this->email = $email;
        $this->password = $password;
        $this->birthdate = $birthdate;
        $this->phone = $phone;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
        $this->auth_token = $auth_token;
        $this->is_activated = $is_activated;
        $this->balance = $balance;
        $this->owner_name = $owner_name;
        $this->iban = $iban;
    }

    public function id(): int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function setPhone(string $phone): self
    {
        $this->phone = $phone;
        return $this;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;
        return $this;
    }

    public function setUpdatedAt(DateTime $updatedAt): self
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function setBalance(float $balance): self
    {
        $this->balance = $balance;
        return $this;
    }
    
    public function setOwnerName(string $owner_name): self
    {
        $this->owner_name = $owner_name;
        return $this;
    }

    public function setIban(string $iban): self
    {
        $this->iban = $iban;
        return $this;
    }
    
    public function email(): string
    {
        return $this->email;
    }

    public function password(): string
    {
        return $this->password;
    }

    public function birthdate(): DateTime
    {
        return $this->birthdate;
    }

    public function phone(): string
    {
        return $this->phone;
    }

    public function createdAt(): DateTime
    {
        return $this->createdAt;
    }

    public function updatedAt(): DateTime
    {
        return $this->updatedAt;
    }

    public function authToken(): string
    {
        return $this->auth_token;
    }

    public function isActivated(): bool
    {
        return $this->is_activated;
    }

    public function balance(): float
    {
        return $this->balance;
    }

    public function ownerName(): string
    {
        return $this->owner_name;
    }

    public function iban(): string
    {
        return $this->iban;
    }

}