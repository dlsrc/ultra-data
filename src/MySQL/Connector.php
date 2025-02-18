<?php declare(strict_types=1);
/**
 * (c) 2005-2025 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Ultra data package.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace Ultra\Data\MySQL;

use mysqli_sql_exception;
use Ultra\Data\Config;
use Ultra\Data\Connector as Connect;
use Ultra\Data\Status;
use Ultra\Fail;

final class Connector extends Connect {
	protected function setState(array $state): bool {
		if (!$this->isConnect()) {
			return false;
		}
		
		try {
			$this->connect->select_db($state['database']);
		}
		catch (mysqli_sql_exception $e) {
			if (1049 != $this->connect->errno && $state['create']) {
				$this->error = new Fail(
					Status::StateNotEstablished,
					'Mysql Error #'.$e->getCode().'. '.$e->getMessage(),
					__FILE__,
					__LINE__-6
				);

				return false;
			}

			try {
				$this->connect->query('CREATE DATABASE '.$state['database'].' DEFAULT CHARACTER SET '.$state['charset']);
				$this->connect->select_db($state['database']);
			}
			catch (mysqli_sql_exception $e) {
				$this->error = new Fail(
					Status::StateNotEstablished,
					'Mysql Error #'.$e->getCode().'. '.$e->getMessage(),
					__FILE__,
					__LINE__-5
				);

				return false;
			}
		}

		return true;
	}

	protected function makeConnect(Config $config): object|false {
		if (!extension_loaded('mysqli')) {
			$this->error = new Fail(
				Status::ExtensionNotLoaded,
				'Extension "mysqli" not loaded.',
				__FILE__,
				__LINE__-5
			);

			return false;	
		}

		try {
			$mysqli = mysqli_init();
		}
		catch (mysqli_sql_exception $e) {
			$this->error = new Fail(
				Status::ConnectionNotInit,
				'Mysql Error #'.$e->getCode().'. '.$e->getMessage(),
				__FILE__,
				__LINE__-5
			);

			return false;
		}
		
		try {
			if (in_array($config->autocommit, ['off', 'no', '0', 0])) {
				$mysqli->options(MYSQLI_INIT_COMMAND, 'SET AUTOCOMMIT = 0');
			}
		}
		catch (mysqli_sql_exception $e) {
			$this->error = new Fail(
				Status::StopAutocommitFailure,
				'Mysql Error #'.$e->getCode().'. '.$e->getMessage(),
				__FILE__,
				__LINE__-5
			);
		}

		try {
			if ('' != $config->connect_timeout) {
				$mysqli->options(MYSQLI_OPT_CONNECT_TIMEOUT, $config->connect_timeout);
			}

			if ('' != $config->read_timeout) {
				$mysqli->options(MYSQLI_OPT_READ_TIMEOUT, $config->read_timeout);
			}
		}
		catch (mysqli_sql_exception $e) {
			$this->error = new Fail(
				Status::TimeoutNotChanged,
				'Mysql Error #'.$e->getCode().'. '.$e->getMessage(),
				__FILE__,
				__LINE__-5
			);
		}

		try {
			$mysqli->options(MYSQLI_SET_CHARSET_NAME, $config->charset);
		}
		catch (mysqli_sql_exception $e) {
			$this->error = new Fail(
				Status::SetCharsetNameFailure,
				'Mysql Error #'.$e->getCode().'. '.$e->getMessage(),
				__FILE__,
				__LINE__-5
			);
		}
		
		try {
			if (!in_array($config->native, ['off', 'no', '0', 0])) {
				$mysqli->options(MYSQLI_OPT_INT_AND_FLOAT_NATIVE, 1);
			}

			if (!in_array($config->real_connect, ['off', 'no', '0', 0])) {
				if ('localhost' == $config->host && '' != $config->socket) {
					$mysqli->real_connect(
						hostname: $config->host,
						username: $config->user,
						password: $config->password,
						socket:   $config->socket,
					);
				}
				else {
					$mysqli->real_connect(
						hostname: $config->host,
						username: $config->user,
						password: $config->password,
						port:(int)$config->port,
					);
				}
			}
			else {
				if ('localhost' == $config->host && '' != $config->socket) {
					$mysqli->connect(
						hostname: $config->host,
						username: $config->user,
						password: $config->password,
						socket:   $config->socket,
					);
				}
				else {
					$mysqli->connect(
						hostname: $config->host,
						username: $config->user,
						password: $config->password,
						port:(int)$config->port,
					);
				}
			}
		}
		catch (mysqli_sql_exception $e) {
			$this->error = new Fail(
				Status::ServerDown,
				'Mysql Error #'.$e->getCode().'. '.$e->getMessage(),
				__FILE__,
				__LINE__-5
			);

			return false;
		}

		return $mysqli;
	}
}
