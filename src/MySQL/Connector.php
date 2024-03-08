<?php declare(strict_types=1);
/**
 * (c) 2005-2024 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Ultra data package.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace Ultra\Data\MySQL;

use mysqli;
use Throwable;
use Ultra\Error;
use Ultra\Data\Code;
use Ultra\Data\Configurable;
use Ultra\Data\Source;

final class Connector extends Source {
	public function getType(): string {
		return 'mysql';
	}

	/**
	* Проверить установлено или нет соединение с источником доанных.
	*/
	protected function isConnect(): bool {
		return (is_object($this->conn) && !$this->conn->connect_error);
	}

	protected function setState(array $state): bool {
		if (!$this->isConnect()) {
			return false;
		}

		try {
			$this->conn->set_charset($state['charset']);
			$this->conn->select_db($state['database']);
		}
		catch (Throwable) {
			if (1049 == $this->conn->errno && $state['create']) {
				if (!$this->conn->query('SET CHARACTER SET '.$state['charset'])) {
					Error::log($this->conn->error, Code::State);
					return false;
				}

				if (!$this->conn->query('CREATE DATABASE `'.$state['database'].'` DEFAULT CHARACTER SET '.$state['charset'])) {
					Error::log($this->conn->error, Code::State);
					return false;
				}
				elseif (!$this->conn->select_db($state['database'])) {
					Error::log($this->conn->error, Code::State);
					return false;
				}
			}
		}
		finally {
			if (!$this->conn->query('SET CHARACTER SET '.$state['charset'])) {
				Error::log($this->conn->error, Code::State);
				return false;
			}
		}

		return true;
	}

	protected function makeConnect(Configurable $config): void {
		$this->conn = @new mysqli(
			$config->host,
			$config->user,
			$config->password
		);
	}

	protected function registerError(): void {
		$conf   = Config::get($this->csname());
		$input  = $conf->lang;
		$output = $conf->charset;

		if ($input == $output) {
			$error  = $this->conn->connect_error;
		}
		else {
			if ('utf8' == $input) {
				$input = 'utf-8';
			}

			if ('utf8' == $output) {
				$output = 'utf-8';
			}

			if ('koi8r' == $input) {
				$input = 'koi8-r';
			}

			if ('koi8r' == $output) {
				$output = 'koi8-r';
			}

			if ($input == $output) {
				$error  = $this->conn->connect_error;
			}
			else {
				$error = \iconv($input, $output, $this->conn->connect_error);
			}
		}

		if ($this->conn->connect_errno > 1999) {
			Error::log($error, Code::Down);
		}
		else {
			Error::log($error, Code::Link);
		}
	}
}
