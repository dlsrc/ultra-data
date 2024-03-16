<?php declare(strict_types=1);

namespace Ultra\Data;

use Ultra\Fail;
use Ultra\Instance;
use Ultra\State;

abstract class Provider implements State {
	use Instance;

	abstract protected function setup(Config $config, Driver $driver);
	private static array $_provider = [];

	public readonly Connector $connector;
	public readonly string $name;
	public readonly array $state;

	protected function __construct(Config $config, Connector $connector, Driver $driver, string $name) {
		$this->connector = $connector;
		$this->name      = $name;
		$this->state     = $config->getStateId();
		$this->setup($config, $driver);
	}

	public static function get(Contract $contract, string $dsn): State {
		$name = $contract->name.'::'.$dsn;
		self::$_provider[$name] ??= self::_make($contract, $name, $dsn);
		return self::$_provider[$name];
	}

	public static function __callStatic(string $name, array $arguments): State {
		if (!$contract = Contract::getCaseByName($name)) {
			return new Fail(Status::UnknownContractorName, 'Unknown contractor name: "'.$name.'"', __FILE__, __LINE__);
		}

		if (!isset($arguments[0]) || !is_string($arguments[0])) {
			return new Fail(Status::MissingArgumentDSN, 'Need DSN string argument to get contract: "'.$name.'", NULL given.', __FILE__, __LINE__);
		}

		return self::get($contract, $arguments[0]);
	}

	private static function _make(Contract $contract, string $name, string $dsn): State {
		$config = Source::get($dsn)->follow(Config::get(...));

		if (!$config->valid()) {
			return $config;
		}

		$connector = Connector::get($config);

		if (!$connector->valid()) {
			return $connector;
		}

		$driver = Driver::get($connector);

		if (!$driver->valid()) {
			return $driver;
		}

		return match ($contract) {
			Contract::Cache   => new Cache($config, $connector, $driver, $name),
			Contract::Browser => new Browser($config, $connector, $driver, $name),
		};
	}
}
