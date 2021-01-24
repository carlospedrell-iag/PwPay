<?php

declare(strict_types=1);

namespace Pw\SlimApp\Model;

use \DateTime;

class MoneyCharge extends Transaction
{
    public function __construct(
        int $user_id,
        float $amount,
        DateTime $created_at
    ) {
        parent::__construct($user_id, $amount, $created_at);  
    }

}