<?php declare(strict_types=1);

namespace Ultra\Data;

use Ultra\Fail;
use Ultra\Instance;
use Ultra\State;

abstract class Driver implements State {
	use Instance;

	private static array $_driver = [];
	readonly public string $type;

	protected function __construct(Connector $connector) {
		$this->type = $connector->type;
	}

	public static function get(Connector $connector): State {
		self::$_driver[$connector->type] ??= match ($connector->type) {
			'mysql', 'mariadb' => new namespace\MySQL\Driver($connector),
			'sqlite' => new namespace\SQLite\Driver($connector),
			'pgsql' => new namespace\PgSQL\Driver($connector),
			'memcache' =>  new namespace\Memcache\Driver($connector),
			default => new Fail(Status::MaintenanceFreeConnection, 'Maintenance free connection type: '.$connector::class, __FILE__, __LINE__),
		};

		return self::$_driver[$connector->type];
	}
}
