<?php declare(strict_types=1);
/**
 * (c) 2005-2024 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Ultra data package.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace Ultra\Data;

use Ultra\Generic\Called;
use Ultra\Generic\Mutable;
use Ultra\Generic\Name;
use Ultra\Generic\NamedGetter;
use Ultra\Generic\Setter;
/**
* Интерфейс конфигурации подключения к источнику данных.
*/
abstract class Config implements Mutable, Called {
	use Name;
	use NamedGetter;
	use Setter;

	protected function __construct(array $state = [], string $name = '') {
		if ('' == $name) {
			$this->_name = \get_class($this);
		}
		else {
			$this->_name = $name;
		}

		$this->initialize();
	}
}
