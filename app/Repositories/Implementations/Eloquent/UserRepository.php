<?php

namespace App\Repositories\Implementations\Eloquent;

use App\Models\User;
use App\Repositories\Contracts\UserRepositoryContract;
use Illuminate\Support\Collection;

class UserRepository implements UserRepositoryContract
{
    public function findOrFail(int $id): User
    {
        return User::query()->findOrFail($id);
    }

    public function findById(int $id): ?User
    {
        return User::query()->find($id);
    }

    public function findByEmail(string $email): ?User
    {
        return User::query()
            ->where('email', $email)
            ->first();
    }

    public function create(array $data): User
    {
        return User::query()->create($data);
    }

    public function update(int $id, array $data): User
    {
        $user = User::query()->findOrFail($id);
        $user->fill($data);
        $user->save();

        return $user;
    }

    public function delete(int $id): void
    {
        User::query()->whereKey($id)->delete();
    }

    public function list(int $limit = 50, int $offset = 0): Collection
    {
        return User::query()
            ->orderBy('id', 'desc')
            ->limit($limit)
            ->offset($offset)
            ->get();
    }
}
