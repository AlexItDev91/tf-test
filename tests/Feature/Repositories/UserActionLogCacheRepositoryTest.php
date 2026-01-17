<?php

use App\Enums\UserAction;
use App\Repositories\Contracts\UserActionLogRepositoryContract;
use App\Repositories\Implementations\Cached\UserActionLogCacheRepository;

beforeEach(function () {
    $this->inner = Mockery::mock(UserActionLogRepositoryContract::class);
    $this->repository = new UserActionLogCacheRepository($this->inner);
});

test('it delegates log to inner', function () {
    $this->inner->shouldReceive('log')->once()->with(1, UserAction::CART_ADD, null, []);

    $this->repository->log(1, UserAction::CART_ADD);

    expect(true)->toBeTrue(); // Assertion for completion
});
