<?php

namespace App\Repositories\Contracts;

use App\Enums\UserAction;

interface UserActionLogRepositoryContract
{
    public function log(
        int $userId,
        UserAction $action,
        array $context = []
    ): void;
}
