<?php declare(strict_types=1);
/**
 * (c) 2005-2024 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Ultra data package.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace Ultra\Data\SQLite;

final class Browser extends \Ultra\Data\Browser {
	public function patch($field): string {
		return '"'.$field.'"';
	}

	protected function sqlAffected(): int {
		return $this->connect->changes();
	}

	protected function sqlErrno(): int {
		return $this->connect->lastErrorCode();
	}

	protected function sqlError(): string {
		return $this->connect->lastErrorMsg();
	}

	protected function sqlEscape(string $string): string {
		return $this->connect->escapeString($string);
	}

	protected function sqlFetchArray(): array|null|false {
		return $this->result->fetchArray(\SQLITE3_BOTH);
	}

	protected function sqlFetchAssoc(): array|null|false {
		return $this->result->fetchArray(\SQLITE3_ASSOC);
	}

	protected function sqlFetchRow(): array|null|false {
		return $this->result->fetchArray(\SQLITE3_NUM);
	}

	protected function sqlFree(): void {
		$this->result->finalize();
	}

	protected function sqlInsertId(): int {
		return $this->connect->lastInsertRowID();
	}

	protected function sqlNumFields(): int {
		return $this->result->numColumns();
	}

	protected function sqlNumRows(): int {
		return 1;
	}

	protected function sqlQuery(string $query): object|bool {
		return $this->connect->query($query);
	}

	protected function sqlResult(): string {
		$row = $this->result->fetchArray(\SQLITE3_NUM);

		if ($row && !empty($row)) {
			return $row[0];
		}

		return '';
	}

	protected function sqlUquery(string $query): object|bool {
		return $this->connect->query($query);
	}
}
