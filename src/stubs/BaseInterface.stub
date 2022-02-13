<?php

namespace App\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

interface EloquentRepositoryInterface
{
    public function all(array $columns = ['*'], array $relations = []): Collection;

    public function allActive(array $columns = ['*'], array $relations = []): Collection;

    public function allInActive(array $columns = ['*'], array $relations = []): Collection;

    public function allWithPaginate(int $parPage, array $relations = []): object;

    public function allTrashed(): Collection;

    public function newObject(): ?Model;

    public function findById(
        int   $modelId,
        array $columns = ['*'],
        array $relations = [],
        array $appends = []
    ): ?Model;

    public function findTrashedById(int $modelId): ?Model;

    public function findOnlyTrashedById(int $modelId): ?Model;

    public function store(array $payload): ?Model;

    public function update(int $modelId, array $payload): bool;

    public function deleteById(int $modelId): bool;

    public function restoreById(int $modelId): bool;

    public function permanentlyDeleteById(int $modelId): bool;

    public function allInArray(string $key, string $name): array;

    public function allActiveInArray(string $key, string $name): array;
}
