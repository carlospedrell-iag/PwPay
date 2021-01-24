<?php

declare(strict_types=1);

namespace Pw\SlimApp\Model;

use \DateTime;

class MoneySend extends Transaction
{
    private int $recipient_id;
    private string $recipient_email;

    public function __construct(
        int $user_id,
        float $amount,
        DateTime $created_at,
        int $recipient_id
    ) {
        parent::__construct($user_id, $amount, $created_at);  
        $this->recipient_id = $recipient_id;
    }

    public function setRecipientEmail(string $recipient_email): self
    {
        $this->recipient_email = $recipient_email;
        return $this;
    }
    
    public function recipientId(): int
    {
        return $this->recipient_id;
    }

    public function isCompleted(): bool
    {
        return $this->is_completed;
    }

    public function recipientEmail(): string
    {
        return $this->recipient_email;
    }

}