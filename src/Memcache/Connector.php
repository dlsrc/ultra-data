<?php declare(strict_types=1);
/**
 * (c) 2005-2024 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Ultra data package.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace Ultra\Data\Memcache;

use Memcache;
use Ultra\Core;
use Ultra\Error;
use Ultra\IO;
use Ultra\Data\Code;
use Ultra\Data\Configurable;
use Ultra\Data\Source;

final class Connector extends Source {
	public function getType(): string {
		return 'memcache';
	}

	protected function isConnect(): bool {
		return is_object($this->conn);
	}

	protected function setState(array $state): bool {
		return true;
	}

	protected function makeConnect(Configurable $config): void {
		$this->conn = new Memcache;

		if ($this->conn->connect($config->host, $config->port)) {
			$this->conn = false;
		}
	}

	protected function registerError(): void {
		Error::log(
			Core::message('e_tcp_cache', $this->host.':'.$this->port),
			Code::Tcp
		);
	}
}
