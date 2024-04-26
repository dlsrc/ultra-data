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

abstract class Provider implements State {
	use Instance;

	abstract protected function setup(Driver $driver);
	private static array $_provider = [];

	public readonly Connector $connector;
	public readonly array $state;

	protected function __construct(Config $config, Connector $connector, Driver $driver) {
		$this->connector = $connector;
		$this->state     = $config->getStateId();
		$this->setup($driver);
	}

	public static function get(Contract $contract, string $dsn): State {
		$name = $contract->name.'::'.$dsn;
		self::$_provider[$name] ??= self::_make($contract, $dsn);
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

	private static function _make(Contract $contract, string $dsn): State {
		$states = Source::get($dsn)->pipe(Config::get(...), Connector::get(...), Driver::get(...));
		
		if (!$states[0]->valid()) {
			return $states[0];
		}

		return match ($contract) {
			Contract::Cache   => new Cache($states[1], $states[2], $states[3]),
			Contract::Browser => new Browser($states[1], $states[2], $states[3]),
		};
	}
}
