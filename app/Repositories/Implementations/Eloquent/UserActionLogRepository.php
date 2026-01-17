<?php

namespace App\Repositories\Implementations\Eloquent;

use App\Enums\UserAction;
use App\Models\UserActionLog;
use App\Repositories\Contracts\UserActionLogRepositoryContract;
use Illuminate\Database\Eloquent\Model;

class UserActionLogRepository implements UserActionLogRepositoryContract
{
    public function log(
        int $userId,
        UserAction|string $action,
        ?Model $subject = null,
        array $metadata = []
    ): void {
        UserActionLog::query()->create([
            'user_id' => $userId,
            'action' => $action,
            'subject_type' => $subject ? $subject::class : null,
            'subject_id' => $subject?->getKey(),
            'metadata' => $metadata ?: null,
            'ip' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
        ]);
    }
}
