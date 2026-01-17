<?php

namespace App\Repositories\Contracts;

interface UserActionLogRepositoryContract
{
    public function log(
        int $userId,
        string $action,
        array $context = []
    ): void;
}
