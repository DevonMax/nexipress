<?php
declare(strict_types=1);

namespace NexiPress\orm;

use PDO;
use NexiPress\orm\Exceptions\QueryException;
use NexiPress\orm\Exceptions\OrmException;
use NexiPress\orm\Contracts\QueryInterface;
use Config;

final class Query implements QueryInterface
{
	protected Connection $connection;
	protected string $table;

	protected array $select = ['*'];
	protected array $where  = [];
	protected array $order  = [];
	protected array $joins  = [];
	protected array $group  = [];

	protected ?int $limit  = null;
	protected ?int $offset = null;

	/* =========================
	   ALLOWED OPERATORS
	========================= */

	protected const ALLOWED_WHERE_OPERATORS = [
		'=', '!=', '<', '<=', '>', '>=',
		'LIKE', 'NOT LIKE',
		'IN', 'NOT IN',
		'IS', 'IS NOT'
	];

	protected const ALLOWED_JOIN_OPERATORS = [
		'=', '!=', '<', '<=', '>', '>='
	];

	protected const ALLOWED_JOIN_TYPES = [
		'INNER', 'LEFT', 'RIGHT'
	];

	protected const ALLOWED_ORDER_DIR = [
		'ASC', 'DESC'
	];

	/* =========================
	   IDENTIFIER VALIDATION
	========================= */

	protected function assertIdentifier(string $value): string
	{
		if ($value === '*') {
			return $value;
		}

		if (!preg_match('/^[A-Za-z0-9_\.]+$/', $value)) {
			throw new QueryException("Invalid SQL identifier: {$value}");
		}

		return $value;
	}

	/* =========================
	   CONSTRUCTOR
	========================= */

	public function __construct(Connection $connection, string $table)
	{
		$this->connection = $connection;
		$this->table      = $this->assertIdentifier($table);
	}

	/* =========================
	   SELECT
	========================= */

	public function select(array|string $columns = '*'): self
	{
		$this->select = array_map(
			fn ($c) => $this->assertIdentifier($c),
			is_array($columns) ? $columns : [$columns]
		);

		return $this;
	}

	/* =========================
	   WHERE
	========================= */

	public function where(string $field, string $op, mixed $value): self
	{
		$op = strtoupper($op);

		if (!in_array($op, self::ALLOWED_WHERE_OPERATORS, true)) {
			throw new QueryException("Invalid WHERE operator: {$op}");
		}

		$this->where[] = [$this->assertIdentifier($field), $op, $value];
		return $this;
	}

	/* =========================
	   JOIN
	========================= */

	public function join(
		string $table,
		string $left,
		string $op,
		string $right,
		string $type = 'INNER'
	): self {
		$op   = strtoupper($op);
		$type = strtoupper($type);

		if (!in_array($type, self::ALLOWED_JOIN_TYPES, true)) {
			throw new QueryException("Invalid JOIN type: {$type}");
		}

		if (!in_array($op, self::ALLOWED_JOIN_OPERATORS, true)) {
			throw new QueryException("Invalid JOIN operator: {$op}");
		}

		$this->joins[] = [
			$type,
			$this->assertIdentifier($table),
			$this->assertIdentifier($left),
			$op,
			$this->assertIdentifier($right)
		];

		return $this;
	}

	/* =========================
	   GROUP BY
	========================= */

	public function groupBy(array|string $fields): self
	{
		$this->group = array_map(
			fn ($f) => $this->assertIdentifier($f),
			is_array($fields) ? $fields : [$fields]
		);

		return $this;
	}

	/* =========================
	   ORDER / LIMIT
	========================= */

	public function orderBy(string $field, string $dir = 'ASC'): self
	{
		$dir = strtoupper($dir);

		if (!in_array($dir, self::ALLOWED_ORDER_DIR, true)) {
			throw new QueryException("Invalid ORDER direction: {$dir}");
		}

		$this->order[] = [$this->assertIdentifier($field), $dir];
		return $this;
	}

	public function limit(int $limit, int $offset = 0): self
	{
		if ($limit < 0 || $offset < 0) {
			throw new QueryException('LIMIT and OFFSET must be positive integers');
		}

		$this->limit  = $limit;
		$this->offset = $offset;
		return $this;
	}

	/* =========================
	   EXECUTION
	========================= */

	public function get(): array
	{
		[$sql, $params] = $this->compileSelect();

		try {
			$stmt = $this->connection->pdo()->prepare($sql);
			$stmt->execute($params);

			$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

			if (Config::get('icecube_log')) {
				nexi_orm_log([
					'level'     => 'debug',
					'operation' => 'select',
					'sql'       => $sql,
					'params'    => $params,
					'rows'      => count($rows),
				]);
			}

			return $rows;

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

			throw new QueryException('Query execution failed', 0, $e);
		}
	}

	public function first(): ?array
	{
		$clone = clone $this;
		$clone->limit(1);

		$rows = $clone->get();
		return $rows[0] ?? null;
	}

	/* =========================
	   SQL COMPILER
	========================= */

	protected function compileSelect(): array
	{
		$sql = 'SELECT ' . implode(', ', $this->select)
			 . ' FROM ' . $this->table;

		$params = [];

		foreach ($this->joins as [$type, $table, $left, $op, $right]) {
			$sql .= " {$type} JOIN {$table} ON {$left} {$op} {$right}";
		}

		if ($this->where) {
			$clauses = [];

			foreach ($this->where as $i => [$field, $operator, $value]) {

				$op = strtoupper($operator);

				if ($op === 'IS' || $op === 'IS NOT') {
					if ($value !== null) {
						throw new OrmException("Operator {$op} supports only NULL");
					}
					$clauses[] = "{$field} {$op} NULL";
					continue;
				}

				if (($op === 'IN' || $op === 'NOT IN')) {
					if (!is_array($value) || $value === []) {
						throw new OrmException("Operator {$op} requires non-empty array");
					}

					$placeholders = [];
					foreach ($value as $k => $v) {
						$p = ":w{$i}_{$k}";
						$placeholders[] = $p;
						$params[$p] = $v;
					}

					$clauses[] = "{$field} {$op} (" . implode(',', $placeholders) . ')';
					continue;
				}

				$param = ":w{$i}";
				$clauses[] = "{$field} {$op} {$param}";
				$params[$param] = $value;
			}

			$sql .= ' WHERE ' . implode(' AND ', $clauses);
		}

		if ($this->group) {
			$sql .= ' GROUP BY ' . implode(', ', $this->group);
		}

		if ($this->order) {
			$order = array_map(
				fn ($o) => "{$o[0]} {$o[1]}",
				$this->order
			);
			$sql .= ' ORDER BY ' . implode(', ', $order);
		}

		if ($this->limit !== null) {
			$sql .= ' LIMIT ' . $this->limit;
			if ($this->offset) {
				$sql .= ' OFFSET ' . $this->offset;
			}
		}

		return [$sql, $params];
	}
}
