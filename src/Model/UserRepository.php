<?php

declare(strict_types=1);

namespace Pw\SlimApp\Model;

interface UserRepository
{
    public function save(User $user): void;
}