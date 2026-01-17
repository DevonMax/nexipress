<?php
declare(strict_types=1);

namespace NexiPress\orm;

final class Icecube
{
	protected Connection $connection;
	protected Schema $schema;

	public function __construct(array $config)
	{
		$this->connection = new Connection($config);
		$this->schema     = new Schema($this->connection);
	}

	public function connection(): Connection
	{
		return $this->connection;
	}

	public function schema(): Schema
	{
		return $this->schema;
	}

	public function table(string $table): Query
	{
		return new Query($this->connection, $table);
	}

	public function repository(string $model): Repository
	{
		return new Repository($this->connection, $model);
	}
}
