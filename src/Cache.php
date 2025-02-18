<?php declare(strict_types=1);
/**
 * (c) 2005-2025 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Ultra data package.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace Ultra\Data;

use Ultra\Instance;
use Ultra\State;

class Cache extends Dictionary implements State {
	use Instance;

	public function add(string $key, mixed $value, int $flag = 0, int $expire = 0): bool {
		return $this->driver->addData($this->connector, $key, $value, $flag, $expire);
	}

	public function gets(array|string $key): array|string {
		return $this->driver->getData($this->connector, $key);
	}

	public function set(string $key, mixed $value, int $flag = 0, int $expire = 0): bool {
		return $this->driver->setData($this->connector, $key, $value, $flag, $expire);
	}

	public function replace(string $key, mixed $value, int $flag = 0, int $expire = 0): bool {
		return $this->driver->replaceData($this->connector, $key, $value, $flag, $expire);
	}

	public function delete(string $key): bool {
		return $this->driver->deleteData($this->connector, $key);
	}

	public function flush(): bool {
		return $this->driver->flushData($this->connector, );
	}
}
