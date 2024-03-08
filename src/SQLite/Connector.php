<?php declare(strict_types=1);
/**
 * (c) 2005-2024 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Ultra data package.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace Ultra\Data\SQLite;

use Error as InternalError;
use Exception;
use SQLite3;
use Ultra\Error;
use Ultra\Data\Code;
use Ultra\Data\Configurable;
use Ultra\Data\Source;

final class Connector extends Source {
	public function getType(): string {
		return 'sqlite';
	}

	protected function isConnect(): bool {
		return is_object($this->conn);
	}

	protected function setState(array $state): bool {
		return true;
	}

	protected function makeConnect(Configurable $config): void {
		try {
			if ('full' == $config->mode) {
				$flag = SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE;
			}
			else {
				$flag = SQLITE3_OPEN_READONLY;
			}

			if ($config->key) {
				$this->conn = @new SQLite3($config->db, $flag, $config->key);
			}
			else {
				$this->conn = @new SQLite3($config->db, $flag);
			}
		}
		catch (Exception $e) {
			$error = Error::log($e->getMessage(), Code::Connect);
			$this->conn = $error->id;
		}
		catch (InternalError $e) {
			$error = Error::log($e->getMessage(), Code::Connect);
			$this->conn = $error->id;
		}
	}

	protected function registerError(): void {
/*
		if (\is_object($link)) return $link->lastErrorMsg();
		elseif (\is_int($link)) return \Ultra\Error::message($link);
		else return '';
*/
		return;
	}
}
