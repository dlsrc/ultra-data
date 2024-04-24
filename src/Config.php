<?php declare(strict_types=1);
/**
 * (c) 2005-2024 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Ultra data package.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace Ultra\Data;

use Ultra\Fail;
use Ultra\Generic\Mutable;
use Ultra\Generic\Setter;
use Ultra\Instance;
use Ultra\State;

/**
 * Конфигурация связывает контракты поставщика данных и коннекторы,
 * закрепляя их состояние доступа к данных, для конкретного контракта.
 */
abstract class Config implements Mutable, State {
	use Instance;
	use Setter;

	/**
	 * Идентификатор поставщика данных.
	 */
	abstract public function getProviderId(): string;
	//abstract public function getContractId(): string;
	
	/**
	 * Идентификатор соединения, — строка параметров,
	 * которых достаточно для подключения к источнику данных.
	 */
	abstract public function getConnectId(): string;
	
	/**
	 * Идентификатор состояния соединения, — строка параметров,
	 * которые требуются для доступа к данным внупри источника.
	 */
	abstract public function getStateId(): array;

	private static array $_config = [];

	protected function __construct(Source $source) {
		$this->initialize();
		$this->_property['name'] = $source->name;
		$this->_property['type'] = '';//$source->type->value;
	}

	public static function get(Source $source): State {
		if (isset(self::$_config[$source->name])) {
			return self::$_config[$source->name];
		}

		(self::$_config[$source->name] = match($source->type) {
			Type::MySQL,
			Type::MariaDB  => new namespace\MySQL\Config($source),
			Type::PgSQL    => new namespace\PgSQL\Config($source),
			Type::SQLite   => new namespace\SQLite\Config($source),
			Type::Memcache => new namespace\Memcache\Config($source),
			default        => new Fail(
								Status::NoConfigurationByType,
								'No configuration for data source "'.$source->type->name.'"',
								__FILE__,
								__LINE__-4
							),

		})->commit($source->configure(...));
		
		return self::$_config[$source->name];
	}

	public static function open(string $name): State {
		if (isset(self::$_config[$name])) {
			return self::$_config[$name];
		}

		return new Fail(Status::NoConfigurationByName, 'No configuration by name "'.$name.'"', __FILE__, __LINE__);
	}
}
