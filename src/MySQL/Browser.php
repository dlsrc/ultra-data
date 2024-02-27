<?php declare(strict_types=1);
/**
 * (c) 2005-2024 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Ultra data package.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace Ultra\Data\MySQL;

final class Browser extends \Ultra\Data\Browser {
	public function patch(string $field): string {
		return '`'.$field.'`';
	}

	protected function sqlAffected(): int {
		return $this->connect->affected_rows;
	}

	protected function sqlErrno(): int {
		return $this->connect->errno;
	}

	protected function sqlError(): string {
		return $this->connect->error;
	}

	protected function sqlEscape(string $string): string {
		return $this->connect->real_escape_string($string);
	}

	protected function sqlFetchArray(): array|null|false {
		return $this->result->fetch_array();
	}

	protected function sqlFetchAssoc(): array|null|false {
		return $this->result->fetch_assoc();
	}

	protected function sqlFetchRow(): array|null|false {
		return $this->result->fetch_row();
	}

	protected function sqlFree(): void {
		$this->result->free();
	}

	protected function sqlInsertId(): int {
		return $this->connect->insert_id;
	}

	protected function sqlNumFields(): int {
		return $this->result->field_count;
	}

	protected function sqlNumRows(): int {
		return $this->result->num_rows;
	}

	protected function sqlQuery(string $query): object|bool {
		return $this->connect->query($query);
	}

	protected function sqlResult(): string {
		if ($row = $this->result->fetch_row()) {
			return $row[0];
		}

		return '';
	}

	protected function sqlUquery(string $query): object|bool {
		return $this->connect->query($query);
	}
}
