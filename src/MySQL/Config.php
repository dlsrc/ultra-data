<?php declare(strict_types=1);
/**
 * (c) 2005-2024 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Ultra data package.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace Ultra\Data\MySQL;

use Ultra\Data\Adjustable;
use Ultra\Data\Config as Container;
use Ultra\Data\Configurable;
use Ultra\Data\Inquirer;

final class Config extends Container implements Configurable, Adjustable {
	protected function initialize(): void {
		// Опции подключения
		$this->_property['host']     = 'localhost';
		$this->_property['user']     = 'root';
		$this->_property['password'] = '';
		// Опция выбора базы данных
		$this->_property['database'] = 'test';
		// Создавать базу данных в случае ее отсутствия на сервере
		$this->_property['create']   = false;
		// Опция сопоставления кодировки соединения с сервером.
		$this->_property['charset']  = 'utf8mb4';
		// Кодировка сообщений от сервера
		$this->_property['lang']     = 'utf8';
		// Префикс в именах таблиц
		$this->_property['prefix']   = '';
		// Метка по умолчанию для замены на префикс в строке запроса
		$this->_property['mark']     = '~';

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
		$this->_property['pref']   = &$this->_property['prefix'];
		$this->_property['px']     = &$this->_property['prefix'];
		$this->_property['mk']     = &$this->_property['mark'];
		$this->_property['l']      = &$this->_property['lang'];
		$this->_property['err']    = &$this->_property['lang'];
	}

	public function getProviderId(): string {
		return
		'h='.$this->_property['host'].
		'&u='.$this->_property['user'].
		'&p='.$this->_property['password'].
		'&db='.$this->_property['database'].
		'&cs='.$this->_property['charset'];
	}

	public function getConnectId(): string {
		return
		'h='.$this->_property['host'].
		'&u='.$this->_property['user'].
		'&p='.$this->_property['password'];
	}

	public function getStateId(): array {
		return [
			'database' => $this->_property['database'],
			'charset'  => $this->_property['charset'],
			'create'   => $this->_property['create']
		];
	}

	public function setPrefix(Inquirer $inquirer): void {
		$inquirer->prefix($this->_property['prefix'], $this->_property['mark']);
	}
}
