<?php
declare(strict_types=1);

namespace NexiPress\orm\Contracts;

interface TransactionManagerInterface
{
    public function begin(): void;

    public function commit(): void;

    public function rollback(): void;

    public function inTransaction(): bool;

    public function run(callable $fn): mixed;


}
