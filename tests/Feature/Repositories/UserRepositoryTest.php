<?php

use App\Models\User;
use App\Repositories\Implementations\Eloquent\UserRepository;
use Illuminate\Database\Eloquent\ModelNotFoundException;

beforeEach(function () {
    $this->repository = new UserRepository();
});

test('it can find a user or fail', function () {
    $user = User::factory()->create();

    $found = $this->repository->findOrFail($user->id);

    expect($found->id)->toBe($user->id);
});

test('it throws exception if user not found in findOrFail', function () {
    $this->repository->findOrFail(99999);
})->throws(ModelNotFoundException::class);

test('it can find a user by id', function () {
    $user = User::factory()->create();

    $found = $this->repository->findById($user->id);

    expect($found->id)->toBe($user->id);
});

test('it returns null if user not found by id', function () {
    expect($this->repository->findById(99999))->toBeNull();
});

test('it can find a user by email', function () {
    $user = User::factory()->create(['email' => 'test@example.com']);

    $found = $this->repository->findByEmail('test@example.com');

    expect($found->id)->toBe($user->id);
});

test('it returns null if user not found by email', function () {
    expect($this->repository->findByEmail('nonexistent@example.com'))->toBeNull();
});

test('it can create a user', function () {
    $data = [
        'name' => 'New User',
        'email' => 'new@example.com',
        'password' => bcrypt('password'),
    ];

    $user = $this->repository->create($data);

    expect($user->name)->toBe('New User');
    $this->assertDatabaseHas('users', ['email' => 'new@example.com']);
});

test('it can update a user', function () {
    $user = User::factory()->create(['name' => 'Old Name']);

    $updatedUser = $this->repository->update($user->id, ['name' => 'New Name']);

    expect($updatedUser->name)->toBe('New Name');
    $this->assertDatabaseHas('users', ['id' => $user->id, 'name' => 'New Name']);
});

test('it can delete a user', function () {
    $user = User::factory()->create();

    $this->repository->delete($user->id);

    $this->assertDatabaseMissing('users', ['id' => $user->id]);
});

test('it can list users', function () {
    User::factory()->count(3)->create();

    $users = $this->repository->list();

    expect($users->count())->toBeGreaterThanOrEqual(3);
});
