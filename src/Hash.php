<?php declare(strict_types=1);

namespace Ultra\Data;

abstract class Hash extends Driver {
	abstract public function addData(Connector $connector, string $key, mixed $value, int $flag = 0, int $expire = 0): bool;
	abstract public function getData(Connector $connector, array|string $key): array|string;
	abstract public function setData(Connector $connector, string $key, mixed $value, int $flag = 0, int $expire = 0): bool;
	abstract public function replaceData(Connector $connector, string $key, mixed $value, int $flag = 0, int $expire = 0): bool;
	abstract public function deleteData(Connector $connector, string $key): bool;
	abstract public function flushData(Connector $connector, ): bool;
}
