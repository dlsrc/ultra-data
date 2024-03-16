<?php declare(strict_types=1);
/**
 * (c) 2005-2024 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Ultra data package.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace Ultra\Data;

use Ultra\Core;
use Ultra\Error;

abstract class Browser implements Inquirer {
	abstract public function patch(string $field): string;
	abstract protected function sqlAffected(): int;
	abstract protected function sqlErrno(): int;
	abstract protected function sqlError(): string;
	abstract protected function sqlEscape(string $string): string;
	abstract protected function sqlFetchArray(): array|null|false;
	abstract protected function sqlFetchAssoc(): array|null|false;
	abstract protected function sqlFetchRow(): array|null|false;
	abstract protected function sqlFree(): void;
	abstract protected function sqlInsertId(): int;
	abstract protected function sqlNumFields(): int;
	abstract protected function sqlNumRows(): int;
	abstract protected function sqlQuery(string $query): object|bool;
	abstract protected function sqlResult(): string;
	abstract protected function sqlUquery(string $query): object|bool;

	private static array $browser = [];
	protected object $connect;
	protected object|bool $result;
	protected Source $connector;
	private array $state;
	private string $pref;
	private string $mark;

	protected function __construct(Source $connector) {
		$this->connector = $connector;
		$this->state     = $connector->getState();
		$this->connect   = $connector->getConnect();
		$this->result    = false;
		$this->pref      = '';
		$this->mark      = '';
	}

	public function esc(string $string): string {
		return $this->sqlEscape($string);
	}

	public function in(array $value, string $or_field=''): string {
		if ('' == $or_field) {
			foreach ($value as &$val) {
				$val = $this->sqlEscape((string) $val);
			}

			return ' IN("'.implode('", "', $value).'") ';
		}
		else {
			foreach ($value as &$val) {
				$val = ' `'.$or_field.'` = "'.$this->sqlEscape((string) $val).'" ';
			}

			return implode('OR', $value);
		}
	}

	public function keys(array $value, string $or_field=''): string {
		return $this->in(array_keys($value), $or_field);
	}

	public static function make(Configurable $config, bool $reset): Inquirer|null {
		if (!$connect = Source::connect($config)) {
			return NULL;
		}

		if (!$connect->correct($config->getStateId())) {
			return NULL;
		}

		$id = $config->getProviderId();

		if (!isset(self::$browser[$id])) {
			switch (get_class($config)) {
			case namespace\MySQL\Config::class:
				$class = namespace\MySQL\Browser::class;
				break;

			case namespace\PgSQL\Config::class:
				$class = namespace\PgSQL\Browser::class;
				break;	

			case namespace\SQLite\Config::class:
				$class = namespace\SQLite\Browser::class;
				break;

			default:
				Error::log(
					Core::message('e_db_browser', get_class($config)),
					Code::Browser
				);

				return NULL;
			}

			self::$browser[$id] = new $class($connect);
			$reset = true;
		}

		if ($reset && $config instanceof Adjustable) {
			$config->setPrefix(self::$browser[$id]);
		}

		return self::$browser[$id];
	}

	public function prefix(string $pref, string $mark='~'): void {
		$this->pref = $pref;
		$this->mark = $mark;
	}

	/**
	* Подготовка соединения и запроса к выполнению.
	*/
	private function prepare(string $query, array $var, bool $unbuf = false, bool $suba = false): bool {
		$this->connector->correct($this->state);

		if ('' !== $this->mark) {
			$query = str_replace($this->mark, $this->pref, $query);
		}

		if (sizeof($var) > 0) {
			$search  = [];
			$replace = [];

			foreach ($var as $key => $val) {
				if (is_array($val)) {
					if ($suba) {
						foreach ($val as $id => $data) {
							$search[]  = '{'.$key.'#'.$id.'}';
							$replace[] = $this->sqlEscape((string)$data);
						}
					}
				}
				else {
					$search[]  = '{'.$key.'}';
					$replace[] = $this->sqlEscape((string)$val);
				}
			}
			
			$query = str_replace($search, $replace, $query);
		}

		if ($unbuf) {
			$this->result = $this->sqlUquery($query);
		}
		else {
			$this->result = $this->sqlQuery($query);
		}

		if (false === $this->result) {
			Error::log($this->sqlError(), Code::Query);
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

		while ($row = $this->sqlFetchRow()) {
			$data[] = $row;
		}

		$this->sqlFree();
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

		while ($row = $this->sqlFetchAssoc()) {
			$data[] = $row;
		}

		$this->sqlFree();
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

		if ($row = $this->sqlFetchRow()) {
			if (!$first_only && array_key_exists(1, $row)) {
				$data[$row[0]] = $row[1];

				while ($row = $this->sqlFetchRow()) {
					$data[$row[0]] = $row[1];
				}
			}
			else {
				$data[$row[0]] = $row[0];

				while ($row = $this->sqlFetchRow()) {
					$data[$row[0]] = $row[0];
				}
			}
		}

		$this->sqlFree();
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

		while ($row = $this->sqlFetchRow()) {
			$data[] = $row[0];
		}

		$this->sqlFree();
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

		while ($row = $this->sqlFetchRow()) {
			$id = $row[0];
			$data[$id] ??= [];
			$data[$id][] = $row;
		}

		$this->sqlFree();
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

		while ($row = $this->sqlFetchAssoc()) {
			$column = array_key_first($row);
			$id = $row[$column];
			$data[$id] ??= [];
			$data[$id][] = $row;
		}

		$this->sqlFree();
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

		while ($row = $this->sqlFetchRow()) {
			$id = array_shift($row);
			$data[$id] ??= [];
			$data[$id][] = $row;
		}

		$this->sqlFree();
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

		while ($row = $this->sqlFetchAssoc()) {
			$id = array_shift($row);
			$data[$id] ??= [];
			$data[$id][] = $row;
		}

		$this->sqlFree();
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

		if ($row = $this->sqlFetchRow()) {
			$data[$row[0]] ??= [];

			for ($i=1; array_key_exists($i, $row); $i++) {
				$data[$row[0]][] = $row[$i];
			}

			while ($row = $this->sqlFetchRow()) {
				$data[$row[0]] ??= [];

				for ($i=1; array_key_exists($i, $row); $i++) {
					$data[$row[0]][] = $row[$i];
				}
			}
		}

		$this->sqlFree();
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

		if ($row = $this->sqlFetchRow()) {
			if (array_key_exists(2, $row)) {
				$data[$row[0]] ??= [];
				$data[$row[0]][$row[1]] = $row[2];

				while ($row = $this->sqlFetchRow()) {
					$data[$row[0]] ??= [];
					$data[$row[0]][$row[1]] = $row[2];
				}
			}
			elseif (array_key_exists(1, $row)) {
				$data[$row[0]] ??= [];
				$data[$row[0]][$row[1]] = $row[1];

				while ($row = $this->sqlFetchRow()) {
					$data[$row[0]] ??= [];
					$data[$row[0]][$row[1]] = $row[1];
				}
			}
		}

		$this->sqlFree();
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

		while ($row = $this->sqlFetchRow()) {
			$data[$row[0]] = $row;
		}

		$this->sqlFree();
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

		if ($row = $this->sqlFetchAssoc()) {
			$column = array_key_first($row);
			$data[$row[$column]] = $row;

			while ($row = $this->sqlFetchAssoc()) {
				$data[$row[$column]] = $row;
			}
		}

		$this->sqlFree();
		return $data;
	}

	/**
	* Вернуть массив результата SQL запроса (первую строку запроса)
	*/
	public function row(string $query, array $value = []): array {
		if (!$this->prepare($query, $value, true)) {
			return [];
		}

		if ($row = $this->sqlFetchArray()) {
			$this->sqlFree();
			return $row;
		}

		$this->sqlFree();
		return [];
	}

	/**
	* Вернуть единственный результат SQL запроса.
	*/
	public function result(string $query, array $value = []): string {
		if (!$this->prepare($query, $value)) {
			return '';
		}

		if (!$this->sqlNumRows()) {
			return '';
		}

		$data = $this->sqlResult();
		$this->sqlFree();
		return $data;
	}

	/** ?
	* Выполнить INSERT SQL запрос, вернуть индекс последней вставленной записи
	*//*
	public function insert($query, array $value=array())
	{
		return $this->prepare($query, $value, true);
	}*/

	/**
	* Выполнить SQL запрос.
	*/
	public function run(string $query, array $value = [], bool $suba = false) {
		return $this->prepare($query, $value, true, $suba);
	}

	/**
	* Выполнить SQL запрос, вернуть количество рядов затронутое запросом.
	*/
	public function affect(string $query, array $value = [], bool $suba=false): int {
		if (!$this->prepare($query, $value, false, $suba)) {
			return 0;
		}

		return $this->sqlAffected();
	}

	/**
	* Получить тип источника данных с которым раборает поставщик данных
	* Соответствует имени расширения.
	*/
	public function getType(): string {
		return $this->connector->getType();
	}

	/**
	* Получить префикс таблиц источника данных с которым раборает поставщик данных
	*/
	public function getPrefix(): string {
		return Config::open($this->connector->csname())->px;
	}

	/**
	* Получить текст ошибки
	*/
	public function error(): string {
		return $this->sqlError();
	}
}
