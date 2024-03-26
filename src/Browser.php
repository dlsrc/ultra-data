<?php declare(strict_types=1);

namespace Ultra\Data;

class Browser extends Provider {
	public readonly SQL $driver;

	protected function setup(Driver $driver) {
		$this->driver = $driver;
	}

	public function esc(string $string): string {
		return $this->driver->escape($this->connector, $string);
	}

	public function in(array $value, string $or_field=''): string {
		if ('' == $or_field) {
			foreach ($value as &$val) {
				$val = $this->driver->escape($this->connector, (string) $val);
			}

			return ' IN("'.implode('", "', $value).'") ';
		}
		else {
			foreach ($value as &$val) {
				$val = ' `'.$or_field.'` = "'.$this->driver->escape($this->connector, (string) $val).'" ';
			}

			return implode('OR', $value);
		}
	}

	public function keys(array $value, string $or_field=''): string {
		return $this->in(array_keys($value), $or_field);
	}

	/**
	* Подготовка соединения и запроса к выполнению.
	*/
	private function prepare(string $query, array $var, bool $unbuf = false, bool $suba = false): bool {
//		$this->connector->correct($this->state); //!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
//!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
		if (!$this->connector->checkState($this)) {
			return false;
		}

		if (sizeof($var) > 0) {
			$search  = [];
			$replace = [];

			foreach ($var as $key => $val) {
				if (is_array($val)) {
					if ($suba) {
						foreach ($val as $id => $data) {
							$search[]  = '{'.$key.'#'.$id.'}';
							$replace[] = $this->driver->escape($this->connector, (string)$data);
						}
					}
				}
				else {
					$search[]  = '{'.$key.'}';
					$replace[] = $this->driver->escape($this->connector, (string)$val);
				}
			}
			
			$query = str_replace($search, $replace, $query);
		}

		if ($unbuf) {
			$this->driver->unbufQuery($this->connector, $query);
		}
		else {
			$this->driver->query($this->connector, $query);
		}

		if (!$this->driver->isResult()) {
			//Error::log($this->driver->error($this->connector), Code::Query);
			///////////////////////////////////////////////////////////////////
			return false;
		}

		return true;
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
	* индексы второго ассоциированы с именами столбцов, указанными в запросе.
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

	public function join(string $query, array $value = []): string {
		if (!$data = $this->column($query, $value)) {
			return '(NULL)';
		}

		if (!empty($data)) {
			return '("'.implode('", "', $data).'")';
		}

		return '("0")';
	}

	/**
	* Вернуть результат SQL запроса в виде трехмерного массива.
	*
	* SQL => SELECT `f_1`, `f_2`, ... `f_n` FROM `t`
	*
	* RETURN array(
	*    
	*   `v_1` => array(
	*       
	*       0   => array(`v_1`, `v_2_1`, ... `v_n_1`),
	*       1   => array(`v_1`, `v_2_2`, ... `v_n_2`),
	*       2   => array(`v_1`, `v_2_3`, ... `v_n_3`),
	*       ..........................................
	*       n-1 => array(`v_1`, `v_2_n`, ... `v_n_n`)
	*
	*    )
	*
	*   `v_2` => array(
	*       
	*       0   => array(`v_2`, `v_2_1`, ... `v_n_1`),
	*       1   => array(`v_2`, `v_2_2`, ... `v_n_2`),
	*       2   => array(`v_2`, `v_2_3`, ... `v_n_3`),
	*       ..........................................
	*       n-1 => array(`v_2`, `v_2_n`, ... `v_n_n`)
	*
	*    )
	*
	*    ...............................................
	* )
	*/
	public function slice(string $query, array $value = []): array {
		if (!$this->prepare($query, $value, true)) {
			return [];
		}

		$data = [];

		while ($row = $this->driver->fetchRow()) {
			$id = $row[0];
			$data[$id] ??= [];
			$data[$id][] = $row;
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

		while ($row = $this->driver->fetchAssoc()) {
			$column = array_key_first($row);
			$id = $row[$column];
			$data[$id] ??= [];
			$data[$id][] = $row;
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
			$id = array_shift($row);
			$data[$id] ??= [];
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

		while ($row = $this->driver->fetchAssoc()) {
			$id = array_shift($row);
			$data[$id] ??= [];
			$data[$id][] = $row;
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
			$data[$row[0]] ??= [];

			for ($i=1; array_key_exists($i, $row); $i++) {
				$data[$row[0]][] = $row[$i];
			}

			while ($row = $this->driver->fetchRow()) {
				$data[$row[0]] ??= [];

				for ($i=1; array_key_exists($i, $row); $i++) {
					$data[$row[0]][] = $row[$i];
				}
			}
		}

		$this->driver->free();
		return $data;
	}

	/**
	* Несколько комбинированных колонок как срезы первого столбца
	*/
	public function combines(string $query, array $value = []): array {
		if (!$this->prepare($query, $value, true)) {
			return [];
		}

		$data = [];

		if ($row = $this->driver->fetchRow()) {
			if (array_key_exists(2, $row)) {
				$data[$row[0]] ??= [];
				$data[$row[0]][$row[1]] = $row[2];

				while ($row = $this->driver->fetchRow()) {
					$data[$row[0]] ??= [];
					$data[$row[0]][$row[1]] = $row[2];
				}
			}
			elseif (array_key_exists(1, $row)) {
				$data[$row[0]] ??= [];
				$data[$row[0]][$row[1]] = $row[1];

				while ($row = $this->driver->fetchRow()) {
					$data[$row[0]] ??= [];
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
	public function result(string $query, array $value = []): string {
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
	public function run(string $query, array $value = [], bool $suba = false): bool {
		return $this->prepare($query, $value, true, $suba);
	}

	/**
	* Выполнить SQL запрос, вернуть количество рядов затронутое запросом.
	*/
	public function affect(string $query, array $value = [], bool $suba=false): int {
		if (!$this->prepare($query, $value, false, $suba)) {
			return 0;
		}

		return $this->driver->affected($this->connector);
	}

	/**
	* Получить тип источника данных с которым раборает поставщик данных
	* Соответствует имени расширения.
	*/
	public function getType(): string {
		return $this->connector->type;
	}

	/**
	* Получить текст ошибки
	*/
	public function error(): string {
		return $this->driver->error($this->connector);
	}
}
