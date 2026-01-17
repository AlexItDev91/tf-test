<?php

use App\Enums\UserAction;
use App\Models\User;
use App\Repositories\Implementations\Eloquent\UserActionLogRepository;

beforeEach(function () {
    $this->repository = new UserActionLogRepository();
});

test('it can log a user action', function () {
    $user = User::factory()->create();

    $this->repository->log($user->id, UserAction::CART_ADD, null, ['key' => 'value']);

    $this->assertDatabaseHas('user_action_logs', [
        'user_id' => $user->id,
        'action' => 'cart.add',
    ]);
});

test('it can log a user action with subject', function () {
    $user = User::factory()->create();
    $product = \App\Models\Product::factory()->create();

    $this->repository->log($user->id, UserAction::CART_ADD, $product);

    $this->assertDatabaseHas('user_action_logs', [
        'user_id' => $user->id,
        'action' => 'cart.add',
        'subject_type' => $product::class,
        'subject_id' => $product->id,
    ]);
});
