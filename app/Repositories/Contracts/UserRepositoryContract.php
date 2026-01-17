<?php

namespace App\Repositories\Contracts;

use App\Models\User;
use Illuminate\Support\Collection;

interface UserRepositoryContract
{
    public function findOrFail(int $id): User;

    public function findById(int $id): ?User;

    public function findByEmail(string $email): ?User;

    public function create(array $data): User;

    public function update(int $id, array $data): User;

    public function delete(int $id): void;

    public function list(int $limit = 50, int $offset = 0): Collection;
}
