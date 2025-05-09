<?php declare(strict_types=1);
/**
 * (c) 2005-2025 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Ultra data package.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace Ultra\Data;

enum Type: string {
	case MariaDB  = 'mariadb';
	case MySQL    = 'mysql';
	case PgSQL    = 'pgsql';
	case SQLite   = 'sqlite';
	case Memcache = 'memcache';
}
