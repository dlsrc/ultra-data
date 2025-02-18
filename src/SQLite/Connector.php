<?php declare(strict_types=1);
/**
 * (c) 2005-2025 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Ultra data package.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace Ultra\Data\SQLite;

use Error;
use Exception;
use SQLite3;
use Ultra\Data\Config;
use Ultra\Data\Connector as Connect;
use Ultra\Data\Status;
use Ultra\Fail;

final class Connector extends Connect {
	public function getDbFile(): string {
		if (preg_match('/^dbname\s*=\s*([^\s]+)\s+/', $this->id, $match) > 0) {
			return $match[1];
		}

		return '';
	}

	public function dbFileExists(): bool {
		if ($file = $this->getDbFile()) {
			return file_exists($file);
		}

		return false;
	}

	protected function setState(array $state): bool {
		if ('' == $state['extras']) {
			return true;
		}

		if (!is_a($state['extras'], Extras::class, true)) {
			return false;
		}

		new $state['extras']($this)->addFunctions();

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
