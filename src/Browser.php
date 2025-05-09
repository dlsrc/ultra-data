<?php declare(strict_types=1);
/**
 * (c) 2005-2025 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Ultra data package.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace Ultra\Data;

use Ultra\Instance;
use Ultra\State;

class Browser extends Storage implements State {
	use Instance;

	/**
	* Подготовка соединения и запроса к выполнению.
	*/
	protected function prepare(string $query, array $var): bool {
		if (!$this->isValidState()) {
			return false;
		}

		if (sizeof($var) > 0) {
			$search  = [];
			$replace = [];

			foreach ($var as $key => $val) {
				$search[]  = '{'.$key.'}';
				
				$replace[] = match (gettype($val)) {
					'string' => $this->driver->escape($this->connector, $val),
					'integer', 'double' => (string) $val,
					'boolean' => (string) (int) $val,
					'NULL' => 'NULL',
					default => '',
				};
			}

			$query = str_replace($search, $replace, $query);
		}

		return $this->exec($query);
	}

	/**
	 * Обработать спецсимволы в строке
	 */
	public function esc(string $string): string {
		return $this->driver->escape($this->connector, $string);
	}

	/**
	 * Вернуть результат SQL запроса в виде двумерного массива.
	 * Массивы первого и второго измерения имеют числовую индексацию.
	 */
	public function rows(string $query, array $value = []): array {
		if (!$this->prepare($query, $value, true)) {
			return [];
		}

		$data = [];

		while ($row = $this->driver->fetchRow()) {
			$data[] = $row;
		}

		$this->driver->free();
		return $data;
	}

	/**
	 * Вернуть результат SQL запроса в виде двумерного массива.
	 * Массивы первого измерения имеют числовую индексацию,
	 * индексы второго ассоциированы с именами столбцов, указанными в запросе
	 */
	public function assoc(string $query, array $value = []): array {
		if (!$this->prepare($query, $value, true)) {
			return [];
		}

		$data = [];

		while ($row = $this->driver->fetchAssoc()) {
			$data[] = $row;
		}

		$this->driver->free();
		return $data;
	}

	/**
	 * Вернуть комбинацию двух полей в виде массива, при этом первое поле будет
	 * являться ключем массива, второе соответствующим значением.
	 */
	public function combine(string $query, array $value = [], bool $first_only = false): array {
		if (!$this->prepare($query, $value, true)) {
			return [];
		}

		$data = [];

		if ($row = $this->driver->fetchRow()) {
			if (!$first_only && array_key_exists(1, $row)) {
				$data[$row[0]] = $row[1];

				while ($row = $this->driver->fetchRow()) {
					$data[$row[0]] = $row[1];
				}
			}
			else {
				$data[$row[0]] = $row[0];

				while ($row = $this->driver->fetchRow()) {
					$data[$row[0]] = $row[0];
				}
			}
		}

		$this->driver->free();
		return $data;
	}

	/**
	 * Вернуть колонку (список значений одного поля)
	 */
	public function column(string $query, array $value = []): array {
		if (!$this->prepare($query, $value, true)) {
			return [];
		}

		$data = [];

		while ($row = $this->driver->fetchRow()) {
			$data[] = $row[0];
		}

		$this->driver->free();
		return $data;
	}

	/**
	 * Вернуть результат SQL запроса в виде трехмерного массива.
	 *
	 * SQL => SELECT f_1, f_2, ... f_n FROM t
	 *
	 * RETURN array(
	 *
	 *   v_1 => array(
	 *
	 *     0   => array(v_1, v_2_1, ... v_n_1),
	 *     1   => array(v_1, v_2_2, ... v_n_2),
	 *     2   => array(v_1, v_2_3, ... v_n_3),
	 *     ..........................................
	 *     n-1 => array(v_1, v_2_n, ... v_n_n)
	 * 
	 *   )
	 * 
	 *   v_2 => array(
	 *
	 *     0   => array(v_2, v_2_1, ... v_n_1),
	 *     1   => array(v_2, v_2_2, ... v_n_2),
	 *     2   => array(v_2, v_2_3, ... v_n_3),
	 *     ..........................................
	 *     n-1 => array(v_2, v_2_n, ... v_n_n)
	 * 
	 *   )
	 *
	 *   ...............................................
	 * )
	 */
	public function slice(string $query, array $value = []): array {
		if (!$this->prepare($query, $value, true)) {
			return [];
		}

		$data = [];

		while ($row = $this->driver->fetchRow()) {
			$data[$row[0]][] = $row;
		}

		$this->driver->free();
		return $data;
	}

	/**
	 * То же что и slice, но с ассоциативными именами ключей
	 */
	public function aslice(string $query, array $value = []): array {
		if (!$this->prepare($query, $value, true)) {
			return [];
		}

		$data = [];

		if ($row = $this->driver->fetchAssoc()) {
			$column = array_key_first($row);
			$data[$row[$column]][] = $row;

			while ($row = $this->driver->fetchAssoc()) {
				$data[$row[$column]][] = $row;
			}
		}

		$this->driver->free();
		return $data;
	}

	/**
	 * То же что и slice, но ключевое значение удаляется из выборки
	 */
	public function shift(string $query, array $value = []): array {
		if (!$this->prepare($query, $value, true)) {
			return [];
		}

		$data = [];

		while ($row = $this->driver->fetchRow()) {
			$id = $row[0];
			unset($row[0]);
			$data[$id][] = $row;
		}

		$this->driver->free();
		return $data;
	}

	/**
	 * То же что и shift, но с ассоциативными именами ключей
	 */
	public function ashift(string $query, array $value = []): array {
		if (!$this->prepare($query, $value, true)) {
			return [];
		}

		$data = [];

		if ($row = $this->driver->fetchAssoc()) {
			$column = array_key_first($row);
			$id = $row[$column];
			unset($row[$column]);
			$data[$id][] = $row;

			while ($row = $this->driver->fetchAssoc()) {
				$id = $row[$column];
				unset($row[$column]);
				$data[$id][] = $row;
			}
		}

		$this->driver->free();
		return $data;
	}

	/**
	 * Несколько колонок как срезы первого столбца
	 */
	public function columns(string $query, array $value = []): array {
		if (!$this->prepare($query, $value, true)) {
			return [];
		}

		$data = [];

		if ($row = $this->driver->fetchRow()) {
			for ($i=1; array_key_exists($i, $row); $i++) {
				$data[$row[0]][] = $row[$i];
			}

			while ($row = $this->driver->fetchRow()) {
				for ($i=1; array_key_exists($i, $row); $i++) {
					$data[$row[0]][] = $row[$i];
				}
			}
		}

		$this->driver->free();
		return $data;
	}

	/**
	 * Несколько комбинированных колонок как срезы первого столбца.
	 */
	public function combines(string $query, array $value = []): array {
		if (!$this->prepare($query, $value, true)) {
			return [];
		}

		$data = [];

		if ($row = $this->driver->fetchRow()) {
			if (array_key_exists(2, $row)) {
				$data[$row[0]][$row[1]] = $row[2];

				while ($row = $this->driver->fetchRow()) {
					$data[$row[0]][$row[1]] = $row[2];
				}
			}
			elseif (array_key_exists(1, $row)) {
				$data[$row[0]][$row[1]] = $row[1];

				while ($row = $this->driver->fetchRow()) {
					$data[$row[0]][$row[1]] = $row[1];
				}
			}
		}

		$this->driver->free();
		return $data;
	}


	/**
	 * Вернуть результат SQL запроса в виде двумерного массива.
	 * Массивы первого измерения индексированы значением одного из столбцев
	 * выборки (соответственно значения в таком столбце должны быть уникальны,
	 * иначе будет возвращаться последняя строка, array_unique наоборот),
	 * индексы второго ассоциированы с порядком столбцов, указанных в запросе.
	 */
	public function table(string $query, array $value = []): array {
		if (!$this->prepare($query, $value, true)) {
			return [];
		}

		$data = [];

		while ($row = $this->driver->fetchRow()) {
			$data[$row[0]] = $row;
		}

		$this->driver->free();
		return $data;
	}

	/**
	 * Вернуть результат SQL запроса в виде двумерного массива.
	 * Массивы первого измерения индексированы значением одного из столбцев
	 * выборки (соответственно значения в таком столбце должны быть уникальны,
	 * иначе будет возвращаться последняя строка, array_unique наоборот),
	 * индексы второго ассоциированы с именами столбцов, указанными в запросе.
	 */
	public function view(string $query, array $value = []): array {
		if (!$this->prepare($query, $value, true)) {
			return [];
		}

		$data = [];

		if ($row = $this->driver->fetchAssoc()) {
			$column = array_key_first($row);
			$data[$row[$column]] = $row;

			while ($row = $this->driver->fetchAssoc()) {
				$data[$row[$column]] = $row;
			}
		}

		$this->driver->free();
		return $data;
	}

	/**
	 * Вернуть массив результата SQL запроса (первую строку запроса)
	 */
	public function row(string $query, array $value = []): array {
		if (!$this->prepare($query, $value, true)) {
			return [];
		}

		if ($row = $this->driver->fetchArray()) {
			$this->driver->free();
			return $row;
		}

		$this->driver->free();
		return [];
	}

	/**
	 * Вернуть единственный результат SQL запроса.
	 */
	public function result(string $query, array $value = []): bool|int|float|string {
		if (!$this->prepare($query, $value)) {
			return '';
		}

		if (!$this->driver->numRows()) {
			return '';
		}

		$data = $this->driver->result();
		$this->driver->free();
		return $data;
	}

	/**
	 * Выполнить SQL запрос.
	 */
	public function run(string $query, array $value = []): bool {
		return $this->prepare($query, $value);
	}

	/**
	 * Выполнить SQL запрос, вернуть количество рядов затронутое запросом.
	 */
	public function affect(string $query, array $value = []): int {
		if (!$this->prepare($query, $value)) {
			return 0;
		}

		return $this->driver->affected($this->connector);
	}

	/**
	 * Получить текст ошибки
	 */
	public function error(): string {
		return $this->driver->error($this->connector);
	}
}
