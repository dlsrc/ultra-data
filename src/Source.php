<?php declare(strict_types=1);
/**
 * (c) 2005-2024 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Ultra data package.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace Ultra\Data;

use Closure;
use Ultra\State;
use Ultra\Instance;
use Ultra\Pipe;
use Ultra\Pipeline;

class Source implements Pipeline, State {
	use Instance;
	use Pipe;

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
	readonly public Type $type;

	/**
	 * Получить интерфейс состояния источника данных из строки подключения к источнику данных.
	 */
	public static function get(string $dsn): State & Pipeline {
		self::$_source[$dsn] ??= new Dsn($dsn)->parse()->commit(self::_make(...));
		return self::$_source[$dsn];
	}

	/**
	 * Проверить возможность совершить подключение к источнику данных
	 * Первый аргумент, — строка подключения к источнику данных, проверку доступности которого нужно проверить.
	 * Второй необязательный аргумент, — замыкание для перехвата и обработки ошибки.
	 */
	public static function supported(string $dsn, Closure|null $reject = null): bool {
		$connect = self::get($dsn)->follow(Config::get(...))->follow(Connector::get(...));

		if ($connect->valid()) {
			return true;
		}

		if (null !== $reject) {
			$reject($connect);
		}

		return false;
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
		$this->type = Type::from($options['type']);
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
