<?php declare(strict_types=1);
/**
 * (c) 2005-2024 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Ultra data package.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace Ultra\Data\SQLite;

use Ultra\Data\Adjustable;
use Ultra\Data\Config as Container;
use Ultra\Data\Configurable;
use Ultra\Data\Inquirer;

final class Config extends Container implements Configurable, Adjustable {
	protected function initialize(): void {
		// Опция выбора базы данных
		$this->_property['database'] = 'test.db';
		// Режим доступа (полный или только для чтения)
		$this->_property['mode']     = 'full'; // full or read
		// Ключ шифрования
		$this->_property['key']      = '';
		// Префикс в именах таблиц
		$this->_property['prefix']   = '';
		// Метка по умолчанию для замены на префикс в строке запроса
		$this->_property['mark']     = '~';

		// Опции алиасы
		$this->_property['db']     =&$this->_property['database'];
		$this->_property['dbname'] =&$this->_property['database'];
		$this->_property['file']   =&$this->_property['database'];
		$this->_property['access'] =&$this->_property['mode'];
		$this->_property['pref']   =&$this->_property['prefix'];
		$this->_property['px']     =&$this->_property['prefix'];
		$this->_property['mk']     =&$this->_property['mark'];
	}

	public function getProviderId(): string {
		return '&db='.$this->_property['database'].'&mode='.$this->_property['mode'];
	}

	public function getConnectId(): string {
		return '&db='.$this->_property['database'].'&mode='.$this->_property['mode'];
	}

	public function getStateId(): array {
		return [true];
	}

	public function setPrefix(Inquirer $inquirer): void {
		$inquirer->prefix($this->_property['prefix'], $this->_property['mark']);
	}
}
