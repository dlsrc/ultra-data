<?php declare(strict_types=1);
/**
 * (c) 2005-2024 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Ultra data package.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace Ultra\Data\SQLite;

use SQLite3;

abstract class Extras {
	protected int $version;

	abstract public function addFunctions(): void;

	final public function __construct(protected Connector $connector) {
		$this->version = SQLite3::version()['versionNumber'];
	}
}
