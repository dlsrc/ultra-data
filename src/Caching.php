<?php declare(strict_types=1);
/**
 * (c) 2005-2024 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Ultra data package.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace Ultra\Data;

/**
* Интерфейс кеширования данных.
*/
interface Caching {
	public function add(string $key, mixed $value, int $flag = 0, int $expire = 0): bool;
	public function get(array|string $key): array|string;
	public function set(string $key, mixed $value, int $flag = 0, int $expire = 0): bool;
	public function replace(string $key, mixed $value, int $flag = 0, int $expire = 0): bool;
	public function delete(string $key): bool;
	public function flush(): bool;
}
