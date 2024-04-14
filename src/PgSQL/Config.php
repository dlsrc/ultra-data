<?php declare(strict_types=1);
/**
 * (c) 2005-2024 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Ultra data package.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace Ultra\Data\PgSQL;

use Ultra\Data\Config as DataConfig;

final class Config extends DataConfig {
	private const string DEFAULT_PORT = '5432';

	protected function initialize(): void {
		// Опции подключения
		$this->_property['host']     = 'localhost';
		$this->_property['port']     = self::DEFAULT_PORT;
		$this->_property['user']     = 'root';
		$this->_property['password'] = '';
		// Опция выбора базы данных
		$this->_property['dbname']   = 'postgres';
		// Создавать базу данных в случае ее отсутствия на сервере
		$this->_property['create']   = false;
		// Схема
		$this->_property['schema']   = 'public';


		// Опции алиасы
		$this->_property['h']        = &$this->_property['host'];
		$this->_property['server']   = &$this->_property['host'];
		$this->_property['u']        = &$this->_property['user'];
		$this->_property['uid']      = &$this->_property['user'];
		$this->_property['p']        = &$this->_property['password'];
		$this->_property['pwd']      = &$this->_property['password'];
		$this->_property['pass']     = &$this->_property['password'];
		$this->_property['db']       = &$this->_property['dbname'];
		$this->_property['database'] = &$this->_property['dbname'];
	}

	public function getProviderId(): string {
		return 'host='.$this->_property['host'].
		' port='.$this->_property['port'].
		' dbname='.$this->_property['dbname'].
		' user='.$this->_property['user'].
		' password='.$this->_property['password'];
	}

	public function getConnectId(): string {
		return 'host='.$this->_property['host'].
		' port='.$this->_property['port'].
		' dbname='.$this->_property['dbname'].
		' user='.$this->_property['user'].
		' password='.$this->_property['password'];
	}

	public function getStateId(): array {
		return [
			'dbname' => $this->_property['dbname'],
			'schema' => $this->_property['schema'],
		];
	}
}
