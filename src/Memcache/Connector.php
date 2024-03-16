<?php declare(strict_types=1);
/**
 * (c) 2005-2024 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Ultra data package.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace Ultra\Data\Memcache;

use Memcache;
use Ultra\Data\Config;
use Ultra\Data\Connector as Connect;
use Ultra\Data\Status;
use Ultra\Fail;

final class Connector extends Connect {
	protected function setState(array $state): bool {
		return true;
	}

	protected function makeConnect(Config $config): object|false {
		if (!extension_loaded('memcache')) {
			$this->error = new Fail(Status::ExtensionNotLoaded, 'Extension "Memcache" not loaded.', __FILE__, __LINE__);
			return false;	
		}

		$connect = new Memcache;

		if (!$connect->connect($config->host, $config->port)) {
			$this->error = new Fail(Status::ConnectionRefused, 'Connection to memcach server '.$config->host.':'.$config->port.' refused', __FILE__, __LINE__);
				
			return false;
		}

		return $connect;
	}
}
