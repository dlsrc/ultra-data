<?php declare(strict_types=1);
/**
 * (c) 2005-2024 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Ultra data package.
 * Please see the LICENSE file for copyright and licensing information.
 */
namespace Ultra\Data;

use Ultra\Enum\Cases;

enum Contract {
	use Cases;

	case Cache;
	case Browser;
//	case Holder;
//	case Transact;
}
