<?php declare(strict_types=1);
namespace Ultra\Data\Memcache;

use Ultra\Data\Connector;
use Ultra\Data\Hash;

final class Driver extends Hash {
	public function addData(Connector $connector, string $key, mixed $value, int $flag = 0, int $expire = 0): bool {
		return $connector->connect->add($key, $value, $flag, $expire);
	}

	public function getData(Connector $connector, array|string $key): array|string {
		return $connector->connect->get($key);
	}

	public function setData(Connector $connector, string $key, mixed $value, int $flag = 0, int $expire = 0): bool {
		return $connector->connect->set($key, $value, $flag, $expire);
	}

	public function replaceData(Connector $connector, string $key, mixed $value, int $flag = 0, int $expire = 0): bool {
		return $connector->connect->replace($key, $value, $flag, $expire);
	}

	public function deleteData(Connector $connector, string $key): bool {
		return $connector->connect->delete($key);
	}

	public function flushData(Connector $connector, ): bool {
		return $connector->connect->flush();
	}
}
