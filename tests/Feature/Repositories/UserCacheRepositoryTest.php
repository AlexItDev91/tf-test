<?php

use App\Models\User;
use App\Repositories\Contracts\UserRepositoryContract;
use App\Repositories\Implementations\Cached\UserCacheRepository;
use Illuminate\Support\Facades\Cache;
use Mockery\MockInterface;

beforeEach(function () {
    $this->inner = Mockery::mock(UserRepositoryContract::class);
    $this->repository = new UserCacheRepository($this->inner);
    Cache::clear();
});

test('it delegates findOrFail to inner repository', function () {
    $user = User::factory()->make(['id' => 1]);
    $this->inner->shouldReceive('findOrFail')->once()->with(1)->andReturn($user);

    $result = $this->repository->findOrFail(1);

    expect($result)->toBe($user);
});

test('it caches findById result', function () {
    $user = User::factory()->make(['id' => 1]);
    $this->inner->shouldReceive('findById')->once()->with(1)->andReturn($user);

    // First call - should call inner
    $result1 = $this->repository->findById(1);
    expect($result1->id)->toBe(1);

    // Second call - should return from cache
    $result2 = $this->repository->findById(1);
    expect($result2->id)->toBe(1);
});

test('it caches findByEmail result', function () {
    $user = User::factory()->make(['id' => 1, 'email' => 'test@example.com']);
    $this->inner->shouldReceive('findByEmail')->once()->with('test@example.com')->andReturn($user);

    // First call
    $result1 = $this->repository->findByEmail('test@example.com');
    expect($result1->email)->toBe('test@example.com');

    // Second call
    $result2 = $this->repository->findByEmail('test@example.com');
    expect($result2->email)->toBe('test@example.com');
});

test('it flushes cache on create', function () {
    $user = User::factory()->make(['id' => 1, 'email' => 'test@example.com']);
    $this->inner->shouldReceive('create')->once()->andReturn($user);

    $this->repository->create(['name' => 'New User']);

    expect(Cache::has('user:id:1'))->toBeFalse();
    expect(Cache::has('user:email:test@example.com'))->toBeFalse();
});

test('it flushes cache on update', function () {
    $user = User::factory()->make(['id' => 1, 'email' => 'test@example.com']);
    $this->inner->shouldReceive('update')->once()->with(1, ['name' => 'New Name'])->andReturn($user);

    $this->repository->update(1, ['name' => 'New Name']);

    expect(Cache::has('user:id:1'))->toBeFalse();
    expect(Cache::has('user:email:test@example.com'))->toBeFalse();
});

test('it flushes cache on delete', function () {
    $user = User::factory()->make(['id' => 1, 'email' => 'test@example.com']);
    $this->inner->shouldReceive('findById')->once()->with(1)->andReturn($user);
    $this->inner->shouldReceive('delete')->once()->with(1);

    $this->repository->delete(1);

    expect(Cache::has('user:id:1'))->toBeFalse();
    expect(Cache::has('user:email:test@example.com'))->toBeFalse();
});

test('it caches list result', function () {
    $users = collect([User::factory()->make()]);
    $this->inner->shouldReceive('list')->once()->with(50, 0)->andReturn($users);

    $result1 = $this->repository->list(50, 0);
    $result2 = $this->repository->list(50, 0);

    expect($result1)->toBe($result2);
});
