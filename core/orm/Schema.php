<?php
declare(strict_types=1);

namespace NexiPress\orm;

final class Schema
{
	protected Connection $connection;

	public function __construct(Connection $connection)
	{
		$this->connection = $connection;
	}

	public function columns(string $table): array
	{
		return [];
	}

	public function hasColumn(string $table, string $column): bool
	{
		return true;
	}

	public function primaryKey(string $table): ?string
	{
		return null;
	}
}
