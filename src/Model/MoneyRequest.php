<?php

declare(strict_types=1);

namespace Pw\SlimApp\Model;

use \DateTime;

class MoneyRequest extends Transaction
{

    private int $requester_id;
    private bool $is_completed;
    private string $requester_email;

    public function __construct(
        int $user_id,
        float $amount,
        DateTime $created_at,
        int $requester_id,
        bool $is_completed
    ) {
        parent::__construct($user_id, $amount, $created_at);  
        $this->requester_id = $requester_id;
        $this->is_completed = $is_completed;
    }

    public function setRequesterEmail(string $requester_email): self
    {
        $this->requester_email = $requester_email;
        return $this;
    }
    
    public function requesterId(): int
    {
        return $this->requester_id;
    }

    public function isCompleted(): bool
    {
        return $this->is_completed;
    }

    public function requesterEmail(): string
    {
        return $this->requester_email;
    }

}