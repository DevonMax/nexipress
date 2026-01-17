<?php
declare(strict_types=1);

namespace NexiPress\orm;

use PDO;
use PDOException;
use NexiPress\orm\Exceptions\OrmException;
use NexiPress\orm\Contracts\ConnectionInterface;

final class Connection implements ConnectionInterface
{
	protected PDO $pdo;

	public function __construct(array $config)
	{
		$this->pdo = $this->connect($config);
	}

	protected function connect(array $config): PDO
	{
		if (empty($config['dsn'])) {
			throw new OrmException('Missing DSN for database connection');
		}

		$user     = $config['user']     ?? null;
		$password = $config['password'] ?? null;
		$options  = $config['options']  ?? [];

		$options = $options + [
			PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
			PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
			PDO::ATTR_EMULATE_PREPARES   => false,
		];

		try {
			return new PDO($config['dsn'], $user, $password, $options);
		} catch (PDOException $e) {
			throw new OrmException('Database connection failed', 0, $e);
		}
	}

	public function pdo(): PDO
	{
		return $this->pdo;
	}
}