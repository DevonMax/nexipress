<?php
declare(strict_types=1);

namespace NexiPress\orm;

use PDO;
use NexiPress\orm\Exceptions\OrmException;
use NexiPress\orm\Contracts\RepositoryInterface;
use Config;

final class Repository implements RepositoryInterface
{
	protected Connection $connection;
	protected string $modelClass;

	public function __construct(Connection $connection, string $modelClass)
	{
		if (!is_subclass_of($modelClass, Model::class)) {
			throw new OrmException('Repository requires a Model subclass');
		}

		$this->connection = $connection;
		$this->modelClass = $modelClass;
	}

	public function find(int|string $id): ?Model
	{
		$table = $this->modelClass::table();
		$pk    = $this->modelClass::primaryKey();

		$sql = "SELECT * FROM {$table} WHERE {$pk} = :id LIMIT 1";
		$params = [':id' => $id];

		try {
			$stmt = $this->connection->pdo()->prepare($sql);
			$stmt->execute($params);
			$row = $stmt->fetch(PDO::FETCH_ASSOC);

			if (Config::get('icecube_log')) {
				nexi_orm_log([
					'level'     => 'info',
					'operation' => 'select',
					'sql'       => $sql,
					'params'    => $params,
					'rows'      => $row ? 1 : 0,
				]);
			}

			return $row ? new $this->modelClass($row) : null;

		} catch (\Throwable $e) {

			if (Config::get('icecube_log')) {
				nexi_orm_log([
					'level'     => 'error',
					'operation' => 'select',
					'sql'       => $sql,
					'params'    => $params,
					'error'     => $e->getMessage(),
				]);
			}

			throw new OrmException('Find failed', 0, $e);
		}
	}

	public function findBy(array $where): array
	{
		$table = $this->modelClass::table();

		$clauses = [];
		$params  = [];

		foreach ($where as $k => $v) {
			$clauses[] = "{$k} = :{$k}";
			$params[":{$k}"] = $v;
		}

		$sql = "SELECT * FROM {$table}";
		if ($clauses) {
			$sql .= ' WHERE ' . implode(' AND ', $clauses);
		}

		try {
			$stmt = $this->connection->pdo()->prepare($sql);
			$stmt->execute($params);
			$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

			if (Config::get('icecube_log')) {
				nexi_orm_log([
					'level'     => 'info',
					'operation' => 'select',
					'sql'       => $sql,
					'params'    => $params,
					'rows'      => count($rows),
				]);
			}

			return array_map(
				fn($r) => new $this->modelClass($r),
				$rows
			);

		} catch (\Throwable $e) {

			if (Config::get('icecube_log')) {
				nexi_orm_log([
					'level'     => 'error',
					'operation' => 'select',
					'sql'       => $sql,
					'params'    => $params,
					'error'     => $e->getMessage(),
				]);
			}

			throw new OrmException('FindBy failed', 0, $e);
		}
	}

	public function insert(Model $model): int
	{
		if (!$model->validate()) {
			throw new OrmException('Model validation failed');
		}

		$data  = $model->toArray();
		$table = $this->modelClass::table();

		$cols = array_keys($data);
		$vals = array_map(fn($c) => ':' . $c, $cols);
		$params = array_combine($vals, array_values($data));

		$sql = "INSERT INTO {$table} (" . implode(',', $cols) . ")
				VALUES (" . implode(',', $vals) . ")";

		try {
			$stmt = $this->connection->pdo()->prepare($sql);
			$stmt->execute($params);

			$id = (int)$this->connection->pdo()->lastInsertId();

			if (Config::get('icecube_log')) {
				nexi_orm_log([
					'level'     => 'info',
					'operation' => 'insert',
					'sql'       => $sql,
					'params'    => $params,
				]);
			}

			return $id;

		} catch (\Throwable $e) {

			if (Config::get('icecube_log')) {
				nexi_orm_log([
					'level'     => 'error',
					'operation' => 'insert',
					'sql'       => $sql,
					'params'    => $params,
					'error'     => $e->getMessage(),
				]);
			}

			throw new OrmException('Insert failed', 0, $e);
		}
	}

	public function update(Model $model): bool
	{
		if (!$model->validate()) {
			throw new OrmException('Model validation failed');
		}

		$data  = $model->toArray();
		$table = $this->modelClass::table();
		$pk    = $this->modelClass::primaryKey();

		if (!isset($data[$pk])) {
			throw new OrmException('Missing primary key for update');
		}

		$id = $data[$pk];
		unset($data[$pk]);

		$sets = [];
		$params = [];

		foreach ($data as $k => $v) {
			$sets[] = "{$k} = :{$k}";
			$params[":{$k}"] = $v;
		}

		$params[':id'] = $id;

		$sql = "UPDATE {$table} SET " . implode(', ', $sets) . " WHERE {$pk} = :id";

		try {
			$stmt = $this->connection->pdo()->prepare($sql);
			$ok = $stmt->execute($params);

			if (Config::get('icecube_log')) {
				nexi_orm_log([
					'level'     => $ok ? 'info' : 'error',
					'operation' => 'update',
					'sql'       => $sql,
					'params'    => $params,
				]);
			}

			return $ok;

		} catch (\Throwable $e) {

			if (Config::get('icecube_log')) {
				nexi_orm_log([
					'level'     => 'error',
					'operation' => 'update',
					'sql'       => $sql,
					'params'    => $params,
					'error'     => $e->getMessage(),
				]);
			}

			throw new OrmException('Update failed', 0, $e);
		}
	}

	public function delete(int|string $id): bool
	{
		$table = $this->modelClass::table();
		$pk    = $this->modelClass::primaryKey();

		$sql = "DELETE FROM {$table} WHERE {$pk} = :id";
		$params = [':id' => $id];

		try {
			$stmt = $this->connection->pdo()->prepare($sql);
			$ok = $stmt->execute($params);

			if (Config::get('icecube_log')) {
				nexi_orm_log([
					'level'     => $ok ? 'info' : 'error',
					'operation' => 'delete',
					'sql'       => $sql,
					'params'    => $params,
				]);
			}

			return $ok;

		} catch (\Throwable $e) {

			if (Config::get('icecube_log')) {
				nexi_orm_log([
					'level'     => 'error',
					'operation' => 'delete',
					'sql'       => $sql,
					'params'    => $params,
					'error'     => $e->getMessage(),
				]);
			}

			throw new OrmException('Delete failed', 0, $e);
		}
	}
}