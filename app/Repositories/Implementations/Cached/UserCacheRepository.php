<?php

namespace App\Repositories\Implementations\Cached;

use App\Models\User;
use App\Repositories\Contracts\UserRepositoryContract;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class UserCacheRepository implements UserRepositoryContract
{
    public function __construct(
        private readonly UserRepositoryContract $inner
    ) {}

    public function findOrFail(int $id): User
    {
        return $this->inner->findOrFail($id);
    }

    public function findById(int $id): ?User
    {
        return Cache::remember(
            $this->keyById($id),
            now()->addMinutes(10),
            fn () => $this->inner->findById($id)
        );
    }

    public function findByEmail(string $email): ?User
    {
        return Cache::remember(
            $this->keyByEmail($email),
            now()->addMinutes(10),
            fn () => $this->inner->findByEmail($email)
        );
    }

    public function create(array $data): User
    {
        $user = $this->inner->create($data);

        Cache::forget($this->keyById((int) $user->getKey()));
        Cache::forget($this->keyByEmail($user->email));

        return $user;
    }

    public function update(int $id, array $data): User
    {
        $user = $this->inner->update($id, $data);

        Cache::forget($this->keyById($id));
        Cache::forget($this->keyByEmail($user->email));

        return $user;
    }

    public function delete(int $id): void
    {
        $user = $this->inner->findById($id);

        $this->inner->delete($id);

        if ($user) {
            Cache::forget($this->keyById($id));
            Cache::forget($this->keyByEmail($user->email));
        }
    }

    public function list(int $limit = 50, int $offset = 0): Collection
    {
        return Cache::remember(
            $this->keyList($limit, $offset),
            now()->addMinutes(5),
            fn () => $this->inner->list($limit, $offset)
        );
    }

    private function keyById(int $id): string
    {
        return "user:id:{$id}";
    }

    private function keyByEmail(string $email): string
    {
        return "user:email:{$email}";
    }

    private function keyList(int $limit, int $offset): string
    {
        return "users:list:limit:{$limit}:offset:{$offset}";
    }
}
