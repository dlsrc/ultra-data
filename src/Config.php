<?php declare(strict_types=1);

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
		$this->_property['type'] = $source->type;
	}

	public static function get(Source $src): State {
		if (isset(self::$_config[$src->name])) {
			return self::$_config[$src->name];
		}

		(self::$_config[$src->name] = match($src->type) {
			'mysql',
			'mariadb'  => new namespace\MySQL\Config($src),
			'pgsql'    => new namespace\PgSQL\Config($src),
			'sqlite'   => new namespace\SQLite\Config($src),
			'memcache' => new namespace\Memcache\Config($src),
			default    => new Fail(Status::NoConfiguration, 'No configuration for data source "'.$src->type.'"', __FILE__, __LINE__),
		})->commit($src->configure(...));
		
		return self::$_config[$src->name];
	}
}
