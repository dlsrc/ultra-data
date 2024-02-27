<?php declare(strict_types=1);
/**
 * (c) 2005-2024 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Ultra data package.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace Ultra\Data\Memcache;

final class Cache extends \Ultra\Data\Cache {
	protected function cacheAdd(string $key, mixed $value, int $flag = 0, int $expire = 0): bool {
		return $this->connect->add($key, $value, $flag, $expire);
	}

	protected function cacheGet(array|string $key): array|string {
		return $this->connect->get($key);
	}

	protected function cacheSet(string $key, mixed $value, int $flag = 0, int $expire = 0): bool {
		return $this->connect->set($key, $value, $flag, $expire);
	}

	protected function cacheReplace(string $key, mixed $value, int $flag = 0, int $expire = 0): bool {
		return $this->connect->replace($key, $value, $flag, $expire);
	}

	protected function cacheDelete(string $key): bool {
		return $this->connect->delete($key);		
	}

	protected function cacheFlush(): bool {
		return $this->connect->flush();
	}
}
