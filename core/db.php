<?php
declare(strict_types=1);

use NexiPress\orm\Connection;

final class DB
{
	private static array $connections = [];

	public static function get(string $key = 'default'): Connection
	{
		if (!isset(self::$connections[$key])) {

			$db = Config::get("databases.$key");

			if (!$db) {
				throw new RuntimeException("Database '$key' not configured");
			}

			$dsn = sprintf(
				'mysql:host=%s;dbname=%s;charset=%s',
				$db['host'],
				$db['name'],
				$db['charset'] ?? 'utf8mb4'
			);

			self::$connections[$key] = new Connection([
				'dsn'      => $dsn,
				'user'     => $db['user'],
				'password' => $db['pass'],
				'options'  => [
					PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
				],
			]);
		}

		return self::$connections[$key];
	}
}