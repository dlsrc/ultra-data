<?php declare(strict_types=1);

namespace Ultra\Data;

use Ultra\Fail;
use Ultra\State;
use Ultra\Instance;

class Source implements State {
	use Instance;

	/**
	 * Список источников данных
	 */
	private static array $_source = [];

	/**
	 * Ассоциативный список всех допустимых опций подключения,
	 * извлеченных из строки подключения к источнику данных.
	 * Кроме параметров подключения содержит все опции контракта
	 * и параметры состояния соединения необходимые объекту поставщику данных.
	 */
	private array $_option;

	/**
	 * Имя источника, строка составленная из всех отсортированных опций $_option.
	 */
	readonly public string $name;

	/**
	 * Тип источника
	 */
	readonly public string $type;

	public static function get(string $dsn): State {
		self::$_source[$dsn] ??= (new Dsn($dsn))->parse()->commit(self::_make(...));
		return self::$_source[$dsn];
	}

	public static function supported(string $dsn): bool {
		

		return true;
	}

	public function configure(Config $config): State {
		foreach ($this->_option as $name => $val) {
			$config->$name = $val;
		}

		return $config;
	}

	private function __construct(array $options) {
		$this->_option = $options;
		$this->name = $this->makeName($options);
		$this->type = $options['type'];
	}

	private function makeName(array $options): string {
		ksort($options);
		array_walk($options, fn ($val, $key) => $key.'='.$val);
		return implode(' ', $options);
	}

	private static function _make(State $result): State {
		return new self($result->unwrap());
	}
}
