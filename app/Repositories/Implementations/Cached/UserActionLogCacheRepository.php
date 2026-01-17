<?php

namespace App\Repositories\Implementations\Cached;

use App\Enums\UserAction;
use App\Repositories\Contracts\UserActionLogRepositoryContract;

class UserActionLogCacheRepository implements UserActionLogRepositoryContract
{
    public function __construct(
        private readonly UserActionLogRepositoryContract $inner
    ) {}

    public function log(
        int $userId,
        UserAction $action,
        array $context = []
    ): void {
        $this->inner->log($userId, $action, $context);
    }
}
