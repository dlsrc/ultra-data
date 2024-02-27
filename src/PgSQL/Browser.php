<?php declare(strict_types=1);
/**
 * (c) 2005-2024 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Ultra data package.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace Ultra\Data\PgSQL;

final class Browser extends \Ultra\Data\Browser {
	public function patch(string $field): string {
		return '"'.$field.'"';
	}

	protected function sqlAffected(): int {
		return pg_affected_rows($this->result);
	}

	protected function sqlErrno(): int {
		return pg_result_status($this->result, PGSQL_STATUS_LONG);
	}

	protected function sqlError(): string {
		if (!$error = pg_result_error($this->result)) {
			return '';
		}

		return $error;
	}

	protected function sqlEscape(string $string): string {
		return pg_escape_string($this->connect, $string);
	}

	protected function sqlFetchArray(): array|null|false {
		return pg_fetch_array($this->result);
	}

	protected function sqlFetchAssoc(): array|null|false {
		return pg_fetch_assoc($this->result);
	}

	protected function sqlFetchRow(): array|null|false {
		return pg_fetch_row($this->result);
	}

	protected function sqlFree(): void {
		pg_free_result($this->result);
	}

	protected function sqlInsertId(): int {
		return 0;
	}

	protected function sqlNumFields(): int {
		return pg_num_fields($this->result);
	}

	protected function sqlNumRows(): int {
		return pg_num_rows($this->result);
	}

	protected function sqlQuery(string $query): object|bool {
		return pg_query($this->connect, $query);
	}

	protected function sqlResult(): string {
		if ($row = $this->result->fetch_row()) {
			return $row[0];
		}

		return '';
	}

	protected function sqlUquery(string $query): object|bool {
		return pg_query($this->connect, $query);
	}
}
