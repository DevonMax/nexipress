<?php
declare(strict_types=1);

namespace NexiPress\orm;

use NexiPress\orm\Contracts\TransactionManagerInterface;
use NexiPress\orm\Contracts\ConnectionInterface;
use NexiPress\orm\Exceptions\OrmException;
use Throwable;
use Config;

final class TransactionManager implements TransactionManagerInterface
{
	private ConnectionInterface $connection;

	public function __construct(ConnectionInterface $connection)
	{
		$this->connection = $connection;
	}

	/* =========================
	   CORE API
	========================= */

	public function begin(): void
	{
		if ($this->inTransaction()) {
			throw new OrmException('Transaction already started');
		}

		try {
			$this->connection->pdo()->beginTransaction();

			if (Config::get('icecube_log')) {
				nexi_orm_log([
					'level'     => 'debug',
					'operation' => 'tx-begin',
				]);
			}

		} catch (Throwable $e) {

			if (Config::get('icecube_log')) {
				nexi_orm_log([
					'level'     => 'error',
					'operation' => 'tx-begin',
					'error'     => $e->getMessage(),
				]);
			}

			throw new OrmException('Transaction begin failed', 0, $e);
		}
	}

	public function commit(): void
	{
		if (!$this->inTransaction()) {
			throw new OrmException('No active transaction to commit');
		}

		try {
			$this->connection->pdo()->commit();

			if (Config::get('icecube_log')) {
				nexi_orm_log([
					'level'     => 'debug',
					'operation' => 'tx-commit',
				]);
			}

		} catch (Throwable $e) {

			if ($this->inTransaction()) {
				$this->connection->pdo()->rollBack();

				if (Config::get('icecube_log')) {
					nexi_orm_log([
						'level'     => 'error',
						'operation' => 'tx-rollback-forced',
						'error'     => $e->getMessage(),
					]);
				}
			}

			throw new OrmException('Transaction commit failed', 0, $e);
		}
	}

	public function rollback(): void
	{
		if (!$this->inTransaction()) {
			return;
		}

		try {
			$this->connection->pdo()->rollBack();

			if (Config::get('icecube_log')) {
				nexi_orm_log([
					'level'     => 'debug',
					'operation' => 'tx-rollback',
				]);
			}

		} catch (Throwable $e) {

			if (Config::get('icecube_log')) {
				nexi_orm_log([
					'level'     => 'error',
					'operation' => 'tx-rollback',
					'error'     => $e->getMessage(),
				]);
			}

			throw new OrmException('Transaction rollback failed', 0, $e);
		}
	}

	public function inTransaction(): bool
	{
		return $this->connection->pdo()->inTransaction();
	}

	/* =========================
	   HELPER
	========================= */

	public function run(callable $fn): mixed
	{
		$this->begin();

		try {
			$result = $fn();
			$this->commit();
			return $result;

		} catch (Throwable $e) {
			$this->rollback();
			throw $e;
		}
	}
}