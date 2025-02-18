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
	* Подготовка соединения и запроса к выполнению.
	*/
	protected function prepare(string $query, array $var): bool {
		if (!$this->connector->checkState($this)) {
			return false;
		}

		if (sizeof($var) > 0) {
			$search  = [];
			$replace = [];

			foreach ($var as $key => $val) {
				$search[]  = '{'.$key.'}';
				
				$replace[] = match (gettype($val)) {
					'string' => $this->driver->escape($this->connector, $val),
					'integer', 'double' => (string) $val,
					'boolean' => (string) (int) $val,
					'NULL' => 'NULL',
					default => '',
				};
			}

			$query = str_replace($search, $replace, $query);
		}

		try {
			$this->driver->query($this->connector, $query);
		}
		catch (Throwable) {
			Error::log($this->driver->error($this->connector).PHP_EOL.$query, Status::QueryFailed);
			return false;
		}

		if (!$this->driver->isResult()) {
			Error::log($this->driver->error($this->connector).PHP_EOL.$query, Status::QueryFailed);
			///////////////////////////////////////////////////////////////////
			return false;
		}

		return true;
	}
}
