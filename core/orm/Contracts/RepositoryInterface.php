<?php
declare(strict_types=1);

namespace NexiPress\orm\Contracts;

use NexiPress\orm\Model;

interface RepositoryInterface
{
    /* =========================
       READ
    ========================= */

    public function find(int|string $id): ?Model;

    public function findBy(array $where): array;

    /* =========================
       WRITE
    ========================= */

    public function insert(Model $model): int;

    public function update(Model $model): bool;

    public function delete(int|string $id): bool;
}