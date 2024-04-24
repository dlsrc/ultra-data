<?php declare(strict_types=1);
/**
 * (c) 2005-2024 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Ultra data package.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace Ultra\Data;

use Ultra\Fail;
use Ultra\Instance;
use Ultra\State;

abstract class Driver implements State {
	use Instance;

	private static array $_driver = [];
	readonly public Type $type;

	protected function __construct(Connector $connector) {
		$this->type = $connector->type;
	}

	public static function get(Connector $connector): State {
		self::$_driver[$connector->type->value] ??= match ($connector->type) {
			Type::MySQL,
			Type::MariaDB  => new namespace\MySQL\Driver($connector),
			Type::SQLite   => new namespace\SQLite\Driver($connector),
			Type::PgSQL    => new namespace\PgSQL\Driver($connector),
			Type::Memcache => new namespace\Memcache\Driver($connector),
			default        => new Fail(
				Status::MaintenanceFreeConnection,
				'Maintenance free connection type: '.$connector::class,
				__FILE__,
				__LINE__-4
			),
		};

		return self::$_driver[$connector->type->value];
	}
}
