<?php declare(strict_types=1);
/**
 * (c) 2005-2024 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Ultra data package.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace Ultra\Data;

use Ultra\Core;
use Ultra\Error;

abstract class Source {
	// Проверка состояния коннектора
	abstract protected function isConnect(): bool;

	// Вернуть тип источника данных,
	// возвращаемое значение должно соответствовать имени библиотеки расширения.
	abstract public function getType(): string;

	// Выпонить коррекцию соединения в соответствии с параметрами состояния.
	// Метод используется поставщиками данных.
	// Поставщик данных передает желаемое состояние соединения
	// при наличии которого он может работать.
	// Коннектор должен свериться с текущим состоянием соединения и,
	// если оно отлично от переданного, выполнить коррекцию.
	abstract protected function setState(array $state): bool;

	// Выполнить подключение к источнику данных.
	abstract protected function makeConnect(Configurable $config): void;

	// Отреагировать на ошибку подключения к источнику данных
	abstract protected function registerError(): void;

	/**
	* Пул объектов подключения к источникам данных.
	*/
	private static array $connector = [];

	/**
	* Объект подключения к источнику данных
	*/
	protected object|bool $conn;
	
	/**
	* Состояние соединения,
	* список параметров за которым ведется наблюдение.
	*/
	protected array $state;

	/**
	* Имя конфигурации коннектора
	*/
	protected string $csn;

	/**
	* Получить объект конфигурации для источника данных,
	* определенного строкой подключения.
	* Проверить на корректность тип строки подключения.
	* Для корректной строки вернуть интерфейс Ultra\Data\Configurable,
	* либо вернуть NULL.
	* Для получения ошибочного состояния см. Source::message()
	*/
	final public static function configure(string $dsn): Configurable|null {
		if (Config::exists($dsn)) {
			return Config::open($dsn);
		}

		\parse_str($dsn, $config);

		if (!isset($config['type'])) {
			Error::log(
				Core::message('e_db_dsn_no_type', $dsn),
				Code::Type
			);
			return NULL;
		}

		switch ($config['type']) {
		case 'mariadb':
		case 'mysql':
		case 'mysqli':
			$cs = namespace\MySQL\Config::class;
			break;

		case 'pgsql':
			$cs = namespace\PgSQL\Config::class;
			break;

		case 'sqlite':
		case 'sqlite3':
			$cs = namespace\SQLite\Config::class;
			break;

		case 'memcache':
			$cs = namespace\Memcache\Config::class;
			break;

		default:
			Error::log(
				Core::message('e_db_dsn_type', $config['type']),
				Code::Dsn
			);
			return NULL;
		}

		if (!\extension_loaded($config['type'])) {
			$config['type'] = $config['type'].'.'.PHP_SHLIB_SUFFIX;

			if (PHP_SHLIB_SUFFIX == 'dll') {
				$config['type'] = 'php_'.$config['type'];
			}

			Error::log(
				Core::message('e_db_extension', $config['type']),
				Code::Ext
			);

			return NULL;
		}

		unset($config['type']);

		$conf = $cs::get($dsn);

		foreach ($config as $name => $value) {
			if (!isset($conf->$name)) {
				Error::log(
					Core::message('e_db_dsn_option', $dsn, $name),
					Code::Cs
				);

				Config::drop($dsn);
				return NULL;
			}

			$conf->$name = $value;
		}

		return $conf;
	}

	/**
	* Получить экземпляр коннектора используя строку подключения.
	* В случае успеха вернуть интерфейс Source, иначе вернуть NULL.
	*/
	final public static function connect(Configurable $config): self|null {
		$id = $config->getConnectId();

		if (!isset(self::$connector[$id])) {
			switch (\get_class($config)) {
			case namespace\MySQL\Config::class:
				$class = namespace\MySQL\Connector::class;
				break;

			case namespace\PgSQL\Config::class:
				$class = namespace\PgSQL\Connector::class;
				break;

			case namespace\SQLite\Config::class:
				$class = namespace\SQLite\Connector::class;
					break;

			case namespace\Memcache\Config::class:
				$class = namespace\Memcache\Connector::class;
				break;

			default:
				Error::log(
					Core::message('e_db_connect', \get_class($config)),
					Code::Connect
				);

				return NULL;
			}

			$conn = new $class($config);

			if (!$conn->isConnect()) {
				return NULL;
			}

			self::$connector[$id] = $conn;
		}

		return self::$connector[$id];
	}

	/**
	* Проверить корректность строки подключения
	* и возможность установить рабочее соединение с источником данных
	*/
	final public static function support(string $dsn): bool {
		if (!$config = self::configure($dsn)) {
			return false;
		}

		if (!$connect = self::connect($config)) {
			return false;
		}

		return $connect->correct($config->getStateId());
	}

	/**
	* Вернуть интерфейс доступа к данным Ultra\Data\Inquirer, либо NULL.
	*/
	final public static function browser(string $dsn, bool $reset=false): Inquirer|null {
		if (!$config = self::configure($dsn)) {
			return NULL;
		}

		return Browser::make($config, $reset);
	}

	/**
	* Вернуть интерфейс доступа к данным Ultra\Data\Сaching, либо NULL.
	*/
	final public static function cache(string $dsn): Caching|null {
		if (!$config = self::configure($dsn)) {
			return NULL;
		}

		return Cache::make($config);
	}

	/**
	* Вернуть текущее состояние соединения.
	*/
	final public function getState(): array {
		return $this->state;
	}

	/**
	* Вернуть дескриптор соединения.
	*/
	final public function getConnect(): object {
		return $this->conn;
	}

	/**
	* Вернуть идентификатор конфигурации коннектора
	*/
	final public function csname(): string {
		return $this->csn;
	}

	/**
	* Выполнить коррекцию соединения в соответствии с затребованным состоянием.
	*/
	final public function correct(array $state): bool {
		if ($this->state != $state) {
			$this->state = $state;
			return $this->setState($state);
		}

		return true;
	}

	/**
	* Конструктор коннекторов
	*/
	final protected function __construct(Configurable $config) {
		$this->makeConnect($config);

		if (!$this->isConnect()) {
			$this->registerError();
			return;
		}

		$this->csn = $config->getName();
		$this->state = $config->getStateId();
		$this->setState($this->state);
	}
}
