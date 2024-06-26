<?php declare(strict_types=1);
/**
 * (c) 2005-2024 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Ultra data package.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace Ultra\Data\SQLite;

use Ultra\Data\Connector;
use Ultra\Data\SQL;

final class Driver extends SQL {
	public function affected(Connector $connector): int {
		return $connector->connect->changes();
	}

	public function errno(Connector $connector): int {
		return $connector->connect->lastErrorCode();
	}

	public function error(Connector $connector): string {
		return $connector->connect->lastErrorMsg();
	}

	public function escape(Connector $connector, string $string): string {
		return $connector->connect->escapeString($string);
	}

	public function fetchArray(): array|null|false {
		return $this->result->fetchArray(SQLITE3_BOTH);
	}

	public function fetchAssoc(): array|null|false {
		return $this->result->fetchArray(SQLITE3_ASSOC);
	}

	public function fetchRow(): array|null|false {
		return $this->result->fetchArray(SQLITE3_NUM);
	}

	public function free(): void {
		$this->result->finalize();
	}

	public function insertId(Connector $connector): int {
		return $connector->connect->lastInsertRowID();
	}

	public function numFields(): int {
		return $this->result->numColumns();
	}

	public function numRows(): int {
		return 1;
	}

	public function query(Connector $connector, string $query): void {
		$this->result = $connector->connect->query($query);
	}

	public function result(): bool|int|float|string {
		$row = $this->result->fetchArray(SQLITE3_NUM);

		if ($row && !empty($row)) {
			return $row[0];
		}

		return '';
	}
}
