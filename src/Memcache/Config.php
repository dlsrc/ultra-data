<?php declare(strict_types=1);
/**
 * (c) 2005-2024 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Ultra data package.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace Ultra\DataProtorype\Memcache;

use Ultra\Data\Config as Cfg;

final class Config extends Cfg {
	protected function initialize(): void {
		// Опции подключения
		$this->_property['host'] = 'localhost';
		$this->_property['port'] = '11211';
		// Опции алиасы
		$this->_property['h'] = &$this->_property['host'];
		$this->_property['p'] = &$this->_property['port'];
	}

	public function getProviderId(): string {
		return 'h='.$this->_property['host'].'&p='.$this->_property['port'];
	}

	public function getConnectId(): string {
		return 'h='.$this->_property['host'].'&p='.$this->_property['port'];
	}

	public function getStateId(): array {
		return [true];
	}
}
