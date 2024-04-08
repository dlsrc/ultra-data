<?php declare(strict_types=1);
/**
 * (c) 2005-2024 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Ultra data package.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace Ultra\Data;

abstract class SQL extends Driver {
	protected object|bool $result;

	abstract public function affected(Connector $connector): int;
	abstract public function errno(Connector $connector): int ;
	abstract public function error(Connector $connector): string;
	abstract public function escape(Connector $connector, string $string): string;
	abstract public function fetchArray(): array|null|false;
	abstract public function fetchAssoc(): array|null|false;
	abstract public function fetchRow(): array|null|false;
	abstract public function free(): void;
	abstract public function insertId(Connector $connector): int;
	abstract public function numFields(): int;
	abstract public function numRows(): int;
	abstract public function query(Connector $connector, string $query): void;
	abstract public function result(): bool|int|float|string;

	protected function __construct(Connector $connector) {
		parent::__construct($connector);
		$this->result = false;
	}

	public function isResult(): bool {
		if (false === $this->result) {
			return false;
		}

		return true;
	}
}
