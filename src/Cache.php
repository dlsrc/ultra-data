<?php declare(strict_types=1);
/**
 * (c) 2005-2024 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Ultra data package.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace Ultra\Data;

use Ultra\Core;
use Ultra\Error;

abstract class Cache implements Caching {
	abstract protected function cacheAdd(string $key, mixed $value, int $flag = 0, int $expire = 0): bool;
	abstract protected function cacheGet(array|string $key): array|string;
	abstract protected function cacheSet(string $key, mixed $value, int $flag = 0, int $expire = 0): bool;
	abstract protected function cacheReplace(string $key, mixed $value, int $flag = 0, int $expire = 0): bool;
	abstract protected function cacheDelete(string $key): bool;
	abstract protected function cacheFlush(): bool;

	private static $cache;

	protected $connect;

	private $connector;
	private $state;

	protected function __construct(Source $connector) {
		$this->connector = $connector;
		$this->state     = $connector->getState();
		$this->connect   = $connector->getConnect();
	}

	public static function make(Configurable $config) {
		if (!$connect = Source::connect($config)) {
			return false;
		}

		if (!$connect->correct($config->getStateId())) {
			return false;
		}

		$id = $config->getProviderId();

		if (!isset(self::$cache[$id])) {
			switch (get_class($config)) {
			case namespace\Memcache\Config::class:
				$class = namespace\Memcache\Cache::class;
				break;

			default:
				Error::log(
					Core::message('e_db_cache', get_class($config)),
					Code::Cache
				);

				return false;
			}
		}

		self::$cache[$id] = new $class($connect);
		return self::$cache[$id];
	}

	public function add(string $key, mixed $value, int $flag = 0, int $expire = 0): bool {
		return $this->cacheAdd($key, $value, $flag, $expire);
	}

	public function get(array|string $key): array|string {
		return $this->cacheGet($key);
	}

	public function set(string $key, mixed $value, int $flag = 0, int $expire = 0): bool {
		return $this->cacheSet($key, $value, $flag, $expire);
	}

	public function replace(string $key, mixed $value, int $flag = 0, int $expire = 0): bool {
		return $this->cacheReplace($key, $value, $flag, $expire);
	}

	public function delete(string $key): bool {
		return $this->cacheDelete($key);
	}

	public function flush(): bool {
		return $this->cacheFlush();
	}

	public function getType() {
		return $this->connector->getType();
	}
}

