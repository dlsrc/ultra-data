<?php declare(strict_types=1);

namespace Ultra\Data\MySQL;

use Ultra\Data\Config as DataConfig;

final class Config extends DataConfig {
	protected function initialize(): void {
		// Опции подключения
		$this->_property['host']            = 'localhost';
		$this->_property['port']            = '3306';
		$this->_property['socket']          = '';
		$this->_property['user']            = 'root';
		$this->_property['password']        = '';
		// Опция выбора базы данных
		$this->_property['database']        = 'test';
		// Создавать базу данных в случае ее отсутствия на сервере
		$this->_property['create']          = 'on';
		// Опция сопоставления кодировки соединения с сервером.
		$this->_property['charset']         = 'utf8mb4';
		// Кодировка сообщений от сервера
		$this->_property['lang']            = 'utf8';
		// Автокоммит транзакций
		$this->_property['autocommit']      = 'on';
		// Таймаут соединения в секундах
		$this->_property['connect_timeout'] = '';
		// Таймаут ожидания результата команд
		$this->_property['read_timeout']    = '';
		// Использовать real_connect при подключении
		$this->_property['real_connect']    = 'on';

		// Опции алиасы
		$this->_property['h']      = &$this->_property['host'];
		$this->_property['server'] = &$this->_property['host'];
		$this->_property['u']      = &$this->_property['user'];
		$this->_property['uid']    = &$this->_property['user'];
		$this->_property['p']      = &$this->_property['password'];
		$this->_property['pwd']    = &$this->_property['password'];
		$this->_property['pass']   = &$this->_property['password'];
		$this->_property['db']     = &$this->_property['database'];
		$this->_property['dbname'] = &$this->_property['database'];
		$this->_property['cs']     = &$this->_property['charset'];
		$this->_property['l']      = &$this->_property['lang'];
		$this->_property['err']    = &$this->_property['lang'];
		$this->_property['real']   = &$this->_property['real_connect'];
	}

	public function getProviderId(): string {
		return
		'host='.$this->_property['host'].
		' port='.$this->_property['port'].
		' user='.$this->_property['user'].
		' pass='.$this->_property['password'].
		' dbname='.$this->_property['database'].
		' charset='.$this->_property['charset'];
	}

	public function getConnectId(): string {
		return
		'host='.$this->_property['host'].
		' port='.$this->_property['port'].
		' user='.$this->_property['user'].
		' pass='.$this->_property['password'];
	}

	public function getStateId(): array {
		return [
			'database' => $this->_property['database'],
			'charset'  => $this->_property['charset'],
			'create'   => $this->_property['create']
		];
	}
}
