<?php declare(strict_types=1);
/**
 * (c) 2005-2025 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Ultra data package.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace Ultra\Data\PgSQL;

use Ultra\Data\Connector;
use Ultra\Data\SQL;
use Ultra\Data\SQLMode;

final class Driver extends SQL {
	public function affected(Connector $connector): int {
		return pg_affected_rows($this->result);
	}

	public function errno(Connector $connector): int {
		if ($this->result) {
			return pg_result_status($this->result, PGSQL_STATUS_LONG);
		}

		return pg_connection_status($connector->connect);
	}

	public function error(Connector $connector): string {
		if (!$this->result || !$error = pg_result_error($this->result)) {
			return pg_last_error($connector->connect);
		}

		return $error;
	}

	public function escape(Connector $connector, string $string): string {
		return pg_escape_string($connector->connect, $string);
	}

	public function fetchAll(SQLMode $mode = SQLMode::Num): array {
		return pg_fetch_all($this->result, $this->getMode($mode));
	}

	public function fetchArray(): array|null|false {
		return pg_fetch_array($this->result);
	}

	public function fetchAssoc(): array|null|false {
		return pg_fetch_assoc($this->result);
	}

	public function fetchRow(): array|null|false {
		return pg_fetch_row($this->result);
	}

	public function free(): void {
		pg_free_result($this->result);
	}

	public function getMode(SQLMode $mode): int {
		return match($mode) {
			SQLMode::Assoc => PGSQL_ASSOC,
			SQLMode::Num => PGSQL_NUM,
			SQLMode::Both => PGSQL_BOTH,
		};
	}

	public function insertId(Connector $connector): int {
		return 0;
	}

	public function numFields(): int {
		return pg_num_fields($this->result);
	}

	public function numRows(): int {
		return pg_num_rows($this->result);
	}

	public function query(Connector $connector, string $query): void {
		$this->result = pg_query($connector->connect, $query);
	}

	public function result(): bool|int|float|string {
		if ($row = pg_fetch_row($this->result)) {
			return $row[0];
		}

		return '';
	}
}
