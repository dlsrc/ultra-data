<?php declare(strict_types=1);
/**
 * (c) 2005-2024 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Ultra data package.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace Ultra\Data\PgSQL;

//use Throwable;
use Ultra\Data\Config;
use Ultra\Data\Connector as Connect;
use Ultra\Data\Status;
use Ultra\Fail;

final class Connector extends Connect {
	protected function setState(array $state): bool {
		if (!$this->isConnect()) {
			return false;
		}

		if ('public' != $state['schema']) {
			pg_query($this->connect, 'CREATE SCHEMA IF NOT EXISTS '.$state['schema']);
			pg_query($this->connect, 'SET search_path TO '.$state['schema']);
		}

		return true;
	}

	protected function makeConnect(Config $config): object|false {
		if (!extension_loaded('pgsql')) {
			$this->error = new Fail(Status::ExtensionNotLoaded, 'Extension "pgsql" not loaded.', __FILE__, __LINE__);
			return false;	
		}

		if ($connect = pg_connect($config->getConnectId())) {
			if (PGSQL_CONNECTION_OK != pg_connection_status($connect)) {
				$this->error = new Fail(Status::ConnectionRefused, pg_last_error($connect), __FILE__, __LINE__);
				return false;
			}

			return $connect;

		}

		$this->error = new Fail(Status::ServerDown, 'Data source "'.$config->getConnectId().'" is not available.', __FILE__, __LINE__);
		return false;
	}
}
