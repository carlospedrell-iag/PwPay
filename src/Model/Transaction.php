<?php

declare(strict_types=1);

namespace Pw\SlimApp\Model;

use DateTime;

abstract class Transaction
{
    private int $id;
    private int $user_id;
    private float $amount;
    private DateTime $created_at;
    private string $user_email;
    private string $formatted_date;

    public function __construct(
        int $user_id,
        float $amount,
        DateTime $created_at
    ) {
        $this->user_id = $user_id;
        $this->amount = $amount;
        $this->created_at = $created_at;
        $this->formatted_date = $created_at->format('Y-m-d H:i:s');
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

    public function userId(): int
    {
        return $this->user_id;
    }
    
    public function setUserEmail(string $user_email): self
    {
        $this->user_email = $user_email;
        return $this;
    }

    public function userEmail(): string
    {
        return $this->user_email;
    }

    public function amount(): float
    {
        return $this->amount;
    }

    public function createdAt(): DateTime
    {
        return $this->created_at;
    }

    public function formattedDate(): string
    {
        return $this->formatted_date;
    }

    public function getClassName()
    {
        return (new \ReflectionClass($this))->getShortName();
    }

}