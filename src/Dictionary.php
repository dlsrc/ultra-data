<?php declare(strict_types=1);
/**
 * (c) 2005-2025 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Ultra data package.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace Ultra\Data;

abstract class Dictionary extends Provider {
	public readonly Hash $driver;

	protected function setup(Driver $driver) {
		$this->driver = $driver;
	}
}
