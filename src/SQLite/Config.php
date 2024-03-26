<?php declare(strict_types=1);

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

		// Опции алиасы
		$this->_property['db']       = &$this->_property['dbname'];
		$this->_property['database'] = &$this->_property['dbname'];
		$this->_property['file']     = &$this->_property['dbname'];
		$this->_property['access']   = &$this->_property['mode'];
		$this->_property['pref']     = &$this->_property['prefix'];
	}

	public function getProviderId(): string {
		return 'dbname='.$this->_property['dbname'].' mode='.$this->_property['mode'];
	}

	public function getConnectId(): string {
		return 'dbname='.$this->_property['dbname'].' mode='.$this->_property['mode'];
	}

	public function getStateId(): array {
		return [true];
	}
}
