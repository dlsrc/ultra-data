<?php declare(strict_types=1);
/**
 * (c) 2005-2024 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Ultra data package.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace Ultra\Data;

/**
* Интерфейс конфигурации подключения к источнику данных.
*/
interface Configurable {
	/**
	* Вернуть сторку идентифицирующую поставщика данных.
	*/
	public function getProviderId(): string;

	/**
	* Вернуть сторку идентифицирующую обеъект подключения к источнику данных.
	*/
	public function getConnectId(): string;

	/**
	* Вернуть массив параметров идентифицирующий состояние обеъекта подключения
	*/
	public function getStateId(): array;
}
