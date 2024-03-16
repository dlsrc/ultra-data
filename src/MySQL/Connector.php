<?php declare(strict_types=1);

namespace Ultra\Data\MySQL;

use mysqli;
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

		try {
			$this->connect->set_charset($state['charset']);
			$this->connect->select_db($state['database']);
		}
		catch (Throwable) {
			if (1049 == $this->connect->errno && $state['create']) {
				if (!$this->connect->query('SET CHARACTER SET '.$state['charset'])) {
					$this->error = new Fail(Status::StateNotEstablished, $this->connect->error, __FILE__, __LINE__);
					return false;
				}

				if (!$this->connect->query('CREATE DATABASE `'.$state['database'].'` DEFAULT CHARACTER SET '.$state['charset'])) {
					$this->error = new Fail(Status::StateNotEstablished, $this->connect->error, __FILE__, __LINE__);
					return false;
				}
				elseif (!$this->connect->select_db($state['database'])) {
					$this->error = new Fail(Status::StateNotEstablished, $this->connect->error, __FILE__, __LINE__);
					return false;
				}
			}
		}
		/*finally {
			if (!$this->connect->query('SET CHARACTER SET '.$state['charset'])) {
				$this->error = new Fail(Status::StateNotEstablished, $this->connect->error, __FILE__, __LINE__);
				return false;
			}
		}*/

		return true;
	}

	protected function makeConnect(Config $config): object|false {
		if (!extension_loaded('mysqli')) {
			$this->error = new Fail(Status::ExtensionNotLoaded, 'Extension "mysqli" not loaded.', __FILE__, __LINE__);
			return false;	
		}
		
		$connect = new mysqli;

		if ($connect->connect($config->host, $config->user, $config->password)) {
			return $connect;
		}
		elseif ($this->connect->connect_errno > 1999) {
			$this->error = new Fail(Status::ServerDown, $this->connect->connect_error, __FILE__, __LINE__);
		}
		else {
			$this->error = new Fail(Status::ConnectionRefused, $this->connect->connect_error, __FILE__, __LINE__);
		}

		return false;
	}
}
