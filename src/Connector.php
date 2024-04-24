<?php declare(strict_types=1);
/**
 * (c) 2005-2024 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Ultra data package.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace Ultra\Data;

use Ultra\Fail;
use Ultra\Instance;
use Ultra\State;

abstract class Connector implements State {
	use Instance;

	/**
	 * Пулл коннекторов
	 */
	private static array $_connector = [];

	/**
	* Объект подключения к источнику данных
	*/
	readonly public object|bool $connect;

	/**
	 * Ошибка подключения
	 */
	protected Fail|null $error;

	/**
	 * Строка-идентификатор подключения, получается из Config->getConnectId()
	 */
	readonly public string $id;

	/**
	 * Имя соединения.
	 */
	readonly public string $name;

	/**
	 * Тип источника
	 */
	readonly public Type $type;

	/**
	* Состояние соединения, которое нужно поддерживать.
	* В него входит список параметров, которые могут поменяться в процессе исполнения приложения, например,
	* коннектор может подключиться к серверу и переключаться между разными базами данных на сервере.
	*/
	protected array $state;

	/**
	 * Выпонить коррекцию соединения в соответствии с параметрами состояния. Метод используется поставщиками данных.
	 * Поставщик данных передает желаемое состояние соединения при наличии которого он может работать.
	 * Коннектор должен свериться с текущим состоянием соединения и, если оно отлично от переданного, выполнить коррекцию.
	 */
	abstract protected function setState(array $state): bool;

	/**
	 * Выполнить подключение к источнику данных.
	 */
	abstract protected function makeConnect(Config $config): object|false;

	final public static function get(Config $config): State {
		$name = $config->getConnectId();
		self::$_connector[$name] ??= self::createConnector($config);
		return self::$_connector[$name];
	}

	private static function createConnector(Config $config): State {
		return (match ($config->getType()) {
			Type::MySQL,
			Type::MariaDB  => new namespace\MySQL\Connector($config),
			Type::PgSQL    => new namespace\PgSQL\Connector($config),
			Type::SQLite   => new namespace\SQLite\Connector($config),
			Type::Memcache => new namespace\Memcache\Connector($config),
			default        => new Fail(
				Status::NoSuitableConnector,
				'No suitable connector for '.$config::class,
				__FILE__,
				__LINE__-4
			),
		})->commit(self::check(...));
	}

	private static function check(Connector $connector): State {
		if (isset($connector->error)) {
			return $connector->error;
		}

		return $connector;
	}

	final protected function __construct(Config $config) {
		$this->connect = $this->makeConnect($config);
		$this->id      = $config->getConnectId();
		$this->name    = $config->name;
		$this->type    = Type::from($config->type);
		$this->state   = $config->getStateId();
		$this->setState($this->state);
	}

	final protected function isConnect(): bool {
		//return null === $this->error;
		return is_object($this->connect);
	}

	final public function getConfig(): Config {
		if (!$config = Config::open($this->name)->call()) {
			return Config::get(Source::get($this->name));
		}

		return $config;
	}

	final public function getState(): array {
		return $this->state;
	}

	final public function checkState(Provider $provider): bool {
		if ($provider->state == $this->state) {
			return true;
		}

		if ($this->setState($provider->state)) {
			$this->state = $provider->state;
			return true;
		}

		return false;
	}

	final public function isError(): bool {
		return null !== $this->error;
	}

	final public function error(): Fail|null {
		return $this->error;
	}
}
