<?php
declare(strict_types=1);

namespace NexiPress\orm\Contracts;

interface QueryInterface
{
    /* =========================
       BUILD
    ========================= */

    public function select(array|string $columns = '*'): self;

    public function where(string $field, string $op, mixed $value): self;

    public function join(
        string $table,
        string $left,
        string $op,
        string $right,
        string $type = 'INNER'
    ): self;

    public function groupBy(array|string $fields): self;

    public function orderBy(string $field, string $dir = 'ASC'): self;

    public function limit(int $limit, int $offset = 0): self;

    /* =========================
       EXECUTION
    ========================= */

    public function get(): array;

    public function first(): ?array;
}