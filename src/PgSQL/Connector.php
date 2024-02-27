<?php declare(strict_types=1);
/**
 * (c) 2005-2024 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Ultra data package.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace Ultra\Data\PgSQL;

use Ultra\Error;
use Ultra\Data\Code;
use Ultra\Data\Config;
use Ultra\Data\Configurable;
use Ultra\Data\Source;

final class Connector extends Source {
	public function getType(): string {
		return 'pgsql';
	}

	/**
	* Проверить установлено или нет соединение с источником доанных.
	* @final
	* @access public
	* @param array state
	* @return boolean
	*/
	protected function isConnect(): bool {
		if (!is_object($this->conn)) {
			return false;
		}

		if (PGSQL_CONNECTION_BAD == pg_connection_status($this->conn)) {
			return false;
		}

		return true;
	}

	protected function setState(array $state): bool {
		if (!$this->isConnect()) {
			return false;
		}

		return true;
	}

	protected function makeConnect(Configurable $config): void {
		$this->conn = pg_connect($config->getConnectId());
	}

	protected function registerError(): void {
		if (is_object($this->conn)) {
			Error::log(pg_last_error($this->conn), Code::Link);
			return;
		} 
		
		$cfg = Config::get($this->csname());
		$cs  = 'host='.$cfg->host.' port='.$cfg->port.' dbname='.$cfg->dbname.' user='.$cfg->user.' password=*****';

		$message = 'Failed to connect to database server "'.$cs.'"';

		Error::log($message, Code::Link);
	}
}
