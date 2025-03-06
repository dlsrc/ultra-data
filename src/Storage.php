<?php declare(strict_types=1);
/**
 * (c) 2005-2025 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Ultra data package.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace Ultra\Data;

use Throwable;
use Ultra\Error;

abstract class Storage extends Provider {
	public readonly SQL $driver;
	public readonly bool $native;

	protected function setup(Driver $driver) {
		$this->driver = $driver;
		$this->native = match($driver::class) {
			namespace\MySQL\Driver::class => !in_array($this->connector->getConfig()->native, ['off', 'no', '0', 0]),
			namespace\SQLite\Driver::class => true,
			namespace\PgSQL\Driver::class => false,
		};
	}

	/**
	 * Проверка готовности соединения к выполнению запроса
	 */
	protected function isValidState(): bool {
		if ($this->connector->checkState($this)) {
			return true;
		}

		return false;
	}

	/**
	 * Выполнение запроса
	 */
	protected function exec(string $query): bool {
		try {
			$this->driver->query($this->connector, $query);
		}
		catch (Throwable) {
			Error::log($this->driver->error($this->connector).PHP_EOL.$query, Status::QueryFailed);
			return false;
		}

		if (!$this->driver->isResult()) {
			Error::log($this->driver->error($this->connector).PHP_EOL.$query, Status::QueryFailed);
			return false;
		}

		return true;
	}
}
