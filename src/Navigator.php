<?php declare(strict_types=1);
/**
 * (c) 2005-2025 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Ultra data package.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace Ultra\Data;

use Closure;
use Ultra\Instance;
use Ultra\State;

class Navigator extends Storage implements State {
	use Instance;

	private Query $_query;

	protected function __construct(Config $config, Connector $connector, Driver $driver) {
		parent::__construct($config, $connector, $driver);
		$this->_query = new Query(fn($statement) => $this->driver->escape($this->connector, $statement));
	}

	protected function prepare(string $query): bool {
		if ($this->isValidState()) {
			return $this->exec($query);
		}

		return false;
	}

	public function statement(string $statement, string $marker = ''): void {
		$this->_query->statement($statement, $marker);
	}

	public function query(string $query): self|null {
		if ($this->prepare($query)) {
			return $this;
		}

		return null;
	}

	public function list(string|int|float|bool|Closure|array|null ...$variables): self|null {
		if ($this->prepare($this->_query->map($variables))) {
			return $this;
		}

		return null;
	}

	public function map(array $options): self|null {
		if ($this->prepare($this->_query->map($options))) {
			return $this;
		}

		return null;
	}

	public function share(array $shared, string|int|float|bool|Closure|array|null ...$variables): self|null {
		if ($this->prepare($this->_query->join(array_merge([$shared], $variables)))) {
			return $this;
		}

		return null;
	}

	public function join(array $options): self|null {
		if ($this->prepare($this->_query->join($options))) {
			return $this;
		}

		return null;
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
	public function rows(): array {
		$data = $this->driver->fetchAll(SQLMode::Num);
		$this->driver->free();
		return $data;
	}

	/**
	 * Вернуть результат SQL запроса в виде двумерного массива.
	 * Массивы первого измерения имеют числовую индексацию,
	 * индексы второго ассоциированы с именами столбцов, указанными в запросе
	 */
	public function assoc(): array {
		$data = $this->driver->fetchAll(SQLMode::Assoc);
		$this->driver->free();
		return $data;
	}

	/**
	 * Вернуть комбинацию двух полей в виде массива, при этом первое поле будет
	 * являться ключем массива, второе соответствующим значением.
	 */
	public function combine(int $val = 1, int $key = 0): array {
		$all = $this->driver->fetchAll(SQLMode::Num);
		$this->driver->free();

		$data = [];

		if ($this->_keysExists($all, $val, $key)) {
			foreach ($all as $row) {
				$data[$row[$key]] = $row[$val];
			}
		}

		return $data;
	}

	/**
	 * Вернуть колонку (список значений одного поля)
	 */
	public function column(int $column = 0): array {
		$data = $this->driver->fetchColumn($column);
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
	public function slice(int $key = 0): array {
		$all = $this->driver->fetchAll(SQLMode::Num);
		$this->driver->free();

		$data = [];

		if ($this->_keysExists($all, $key)) {
			foreach ($all as $row) {
				$data[$row[$key]][] = $row;
			}
		}

		return $data;
	}

	/**
	 * То же что и slice, но с ассоциативными именами ключей
	 */
	public function aslice(string $name = ''): array {
		$all = $this->driver->fetchAll(SQLMode::Assoc);
		$this->driver->free();

		$data = [];

		if ($name = $this->_fieldNameExists($all, $name)) {
			foreach ($all as $row) {
				$data[$row[$name]][] = $row;
			}	
		}

		return $data;
	}

	/**
	 * То же что и slice, но ключевое значение удаляется из выборки
	 */
	public function shift(int $key = 0): array {
		$all = $this->driver->fetchAll(SQLMode::Num);
		$this->driver->free();

		$data = [];

		if ($this->_keysExists($all, $key)) {
			foreach ($all as $row) {
				$id = $row[$key];
				unset($row[$key]);
				$data[$id][] = $row;
			}
		}

		return $data;
	}

	/**
	 * То же что и shift, но с ассоциативными именами ключей
	 */
	public function ashift(string $name): array {
		$all = $this->driver->fetchAll(SQLMode::Assoc);
		$this->driver->free();

		$data = [];

		if ($name = $this->_fieldNameExists($all, $name)) {
			foreach ($all as $row) {
				$id = $row[$name];
				unset($row[$name]);
				$data[$id][] = $row;
			}
		}

		return $data;
	}

	/**
	 * Несколько колонок как срезы первого столбца
	 */
	public function columns(): array {
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
	public function combines(): array {
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
	public function table(int $key = 0): array {
		$all = $this->driver->fetchAll(SQLMode::Num);
		$this->driver->free();

		$data = [];

		if ($this->_keysExists($all, $key)) {
			foreach ($all as $row) {
				$data[$row[$key]] = $row;
			}
		}

		return $data;
	}

	/**
	 * Вернуть результат SQL запроса в виде двумерного массива.
	 * Массивы первого измерения индексированы значением одного из столбцев
	 * выборки (соответственно значения в таком столбце должны быть уникальны,
	 * иначе будет возвращаться последняя строка, array_unique наоборот),
	 * индексы второго ассоциированы с именами столбцов, указанными в запросе.
	 */
	public function view(string $name = ''): array {
		$all = $this->driver->fetchAll(SQLMode::Assoc);
		$this->driver->free();

		$data = [];

		if ($name = $this->_fieldNameExists($all, $name)) {
			foreach ($all as $row) {
				$data[$row[$name]] = $row;
			}	
		}

		return $data;
	}

	/**
	 * Вернуть массив результата SQL запроса (первую строку запроса)
	 */
	public function row(): array {
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
	public function result(): bool|int|float|string {
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
	public function run(): true {
		return true;
	}

	/**
	 * Выполнить SQL запрос, вернуть количество рядов затронутое запросом.
	 */
	public function affect(): int {
		return $this->driver->affected($this->connector);
	}

	/**
	 * Получить текст ошибки
	 */
	public function error(): string {
		return $this->driver->error($this->connector);
	}

	private function _keysExists(array &$data, int ...$keys): bool {
		if (!isset($data[0])) {
			return false;
		}

		foreach ($keys as $key) {
			if (!array_key_exists($key, $data[0])) {
				return false;
			}
		}

		return true;
	}

	private function _fieldNameExists(array &$data, string $name): string|false {
		if (!isset($data[0])) {
			return false;
		}

		if ('' == $name) {
			return array_key_first($data[0]);
		}
		
		if (array_key_exists($name, $data[0])) {
			return $name;
		}

		return false;
	}
}
