<?php
declare(strict_types=1);

namespace NexiPress\orm;

abstract class Model
{
	protected array $data = [];

	public function __construct(array $data = [])
	{
		if ($data) {
			$this->fromArray($data);
		}
	}

	public function fromArray(array $data): static
	{
		$this->data = $data;
		return $this;
	}

	public function toArray(): array
	{
		return $this->data;
	}

	/**
	 * Validazione minimale.
	 * Per ora sempre true: verrà collegata allo Schema.
	 */
	public function validate(): bool
	{
		return true;
	}

	/**
	 * Chiave primaria (override nel modello concreto).
	 */
	public static function primaryKey(): string
	{
		return 'id';
	}

	/**
	 * Nome tabella (override nel modello concreto).
	 */
	public static function table(): string
	{
		throw new \LogicException('Model::table() must be implemented');
	}
}
