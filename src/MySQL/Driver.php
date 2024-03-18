<?php declare(strict_types=1);

namespace Ultra\Data\MySQL;

use Ultra\Data\Connector;
use Ultra\Data\SQL;

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

	public function result(): string {
		if ($row = $this->result->fetch_row()) {
			return $row[0];
		}

		return '';
	}

	public function unbufQuery(Connector $connector, string $query): void {
		$this->result = $connector->connect->query($query);
	}
}