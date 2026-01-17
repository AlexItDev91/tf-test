<?php

namespace App\Repositories\Contracts;

use App\Enums\UserAction;
use Illuminate\Database\Eloquent\Model;

interface UserActionLogRepositoryContract
{
    public function log(
        int $userId,
        UserAction|string $action,
        ?Model $subject = null,
        array $metadata = []
    ): void;
}
