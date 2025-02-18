<?php declare(strict_types=1);
/**
 * (c) 2005-2025 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Ultra data package.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace Ultra\Data\SQLite;

use Ultra\Data\Config as DataConfig;

final class Config extends DataConfig {
	protected function initialize(): void {
		// Опция выбора базы данных
		$this->_property['dbname']   = 'test.db';
		// Режим доступа (полный или только для чтения)
		$this->_property['mode']     = 'full'; // full or read
		// Ключ шифрования
		$this->_property['key']      = '';
		// Класс реализующий интерфейс добавления SQL функций
		$this->_property['extras']   = '';

		// Опции алиасы
		$this->_property['db']       = &$this->_property['dbname'];
		$this->_property['database'] = &$this->_property['dbname'];
		$this->_property['file']     = &$this->_property['dbname'];
		$this->_property['access']   = &$this->_property['mode'];
	}

	public function getProviderId(): string {
		return 'dbname='.$this->_property['dbname'].' mode='.$this->_property['mode'];
	}

	public function getConnectId(): string {
		return 'dbname='.$this->_property['dbname'].' mode='.$this->_property['mode'];
	}

	public function getStateId(): array {
		return [
			'extras' => $this->_property['extras'],
		];
	}
}
