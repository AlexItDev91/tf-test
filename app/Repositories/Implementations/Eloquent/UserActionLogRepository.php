<?php

namespace App\Repositories\Implementations\Eloquent;

use App\Models\UserActionLog;
use App\Repositories\Contracts\UserActionLogRepositoryContract;

class UserActionLogRepository implements UserActionLogRepositoryContract
{
    public function log(
        int $userId,
        string $action,
        array $context = []
    ): void {
        UserActionLog::query()->create([
            'user_id' => $userId,
            'action' => $action,
            'context' => $context,
        ]);
    }
}
