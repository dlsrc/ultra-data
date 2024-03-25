<?php declare(strict_types=1);

namespace Ultra\Data\SQLite;

use Error;
use Exception;
use SQLite3;
use Ultra\Data\Config;
use Ultra\Data\Connector as Connect;
use Ultra\Data\Status;
use Ultra\Fail;

final class Connector extends Connect {
	protected function setState(array $state): bool {
		return true;
	}

	protected function makeConnect(Config $config): object|false {
		if (!file_exists($config->dbname)) {
			$dir = dirname($config->dbname);

			if (!is_dir($dir)) {
				if (!mkdir($dir, 0755, true)) {
					if (!file_exists($dir)) {
						$this->error = new Fail(
							Status::SqliteDbMakeDirError,
							'Unable to create database file "'.$config->dbname.'". '.
							'This may be due to the rights to the folder in which the file is created.',
							__FILE__,
							__LINE__ - 5
						);

						return false;
					}
				}
			}
		}

		try {
			if ('full' == $config->mode) {
				$flag = SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE;
			}
			else {
				$flag = SQLITE3_OPEN_READONLY;
			}

			if ($config->key) {
				$connect = @new SQLite3($config->db, $flag, $config->key);
			}
			else {
				$connect = @new SQLite3($config->db, $flag);
			}

			return $connect;
		}
		catch (Exception $e) {
			$this->error = new Fail(Status::ConnectionRefused, $e->getMessage(), __FILE__, __LINE__);
			return false;
		}
		catch (Error $e) {
			$this->error = new Fail(Status::ConnectionRefused, $e->getMessage(), __FILE__, __LINE__);
			return false;
		}
	}
}
