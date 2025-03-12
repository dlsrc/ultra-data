<?php declare(strict_types=1);
/**
 * (c) 2005-2025 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Ultra data package.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace Ultra\Data\MySQL;

use Ultra\Data\Connector;
use Ultra\Data\SQL;
use Ultra\Data\SQLMode;

final class Driver extends SQL {
	public function affected(Connector $connector): int {
		return $connector->connect->affected_rows;
	}

	public function errno(Connector $connector): int {
		return $connector->connect->errno;
	}

	public function error(Connector $connector): string {
		return $connector->connect->error;
	}

	public function escape(Connector $connector, string $string): string {
		return $connector->connect->real_escape_string($string);
	}

	public function fetchAll(SQLMode $mode = SQLMode::Num): array {
		return $this->result->fetch_all($this->getMode($mode));
	}

	public function fetchArray(): array|null|false {
		return $this->result->fetch_array();
	}

	public function fetchAssoc(): array|null|false {
		return $this->result->fetch_assoc();
	}

	public function fetchRow(): array|null|false {
		return $this->result->fetch_row();
	}

	public function free(): void {
		$this->result->free();
	}

	public function getMode(SQLMode $mode): int {
		return match($mode) {
			SQLMode::Assoc => MYSQLI_ASSOC,
			SQLMode::Num => MYSQLI_NUM,
			SQLMode::Both => MYSQLI_BOTH,
		};
	}

	public function insertId(Connector $connector): int {
		return $connector->connect->insert_id;
	}

	public function numFields(): int {
		return $this->result->field_count;
	}

	public function numRows(): int {
		return $this->result->num_rows;
	}

	public function query(Connector $connector, string $query): void {
		$this->result = $connector->connect->query($query);
	}

	public function result(): bool|int|float|string {
		if ($row = $this->result->fetch_row()) {
			return $row[0];
		}

		return '';
	}
}
