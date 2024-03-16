<?php declare(strict_types=1);

namespace Ultra\Data;

use Ultra\Enum\Cases;

enum Contract {
	use Cases;

	case Cache;
	case Browser;
//	case Holder;
//	case Transact;
}
