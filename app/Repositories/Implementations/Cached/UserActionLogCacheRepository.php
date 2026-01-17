<?php

namespace App\Repositories\Implementations\Cached;

use App\Enums\UserAction;
use App\Repositories\Contracts\UserActionLogRepositoryContract;
use Illuminate\Database\Eloquent\Model;

class UserActionLogCacheRepository implements UserActionLogRepositoryContract
{
    public function __construct(
        private readonly UserActionLogRepositoryContract $inner
    ) {}

    public function log(
        int $userId,
        UserAction|string $action,
        ?Model $subject = null,
        array $metadata = []
    ): void {
        $this->inner->log($userId, $action, $subject, $metadata);
    }
}
