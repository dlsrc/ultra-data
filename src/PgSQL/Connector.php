<?php declare(strict_types=1);
/**
 * (c) 2005-2024 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Ultra data package.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace Ultra\Data\PgSQL;

use Throwable;
use Ultra\Data\Config;
use Ultra\Data\Connector as Connect;
use Ultra\Data\Status;
use Ultra\Fail;

final class Connector extends Connect {
	protected function setState(array $state): bool {
		if (!$this->isConnect()) {
			return false;
		}

		return true;
	}

	protected function makeConnect(Config $config): object|false {
		if (!extension_loaded('pgsql')) {
			$this->error = new Fail(Status::ExtensionNotLoaded, 'Extension "pgsql" not loaded.', __FILE__, __LINE__);
			return false;	
		}

		if ($connect = pg_connect($config->getConnectId())) {
			if (PGSQL_CONNECTION_OK == pg_connection_status($connect)) {
				return $connect;
			}
			else {
				$this->error = new Fail(Status::ConnectionRefused, pg_last_error($connect), __FILE__, __LINE__);
				return false;
			}
		}

		$this->error = new Fail(Status::ServerDown, 'Data sooource "'.$config->getConnectId().'" is not available.', __FILE__, __LINE__);
		return false;
	}
}
