<?php declare(strict_types=1);

namespace Ultra\Data;

use Ultra\Condition;

enum Status: int implements Condition {
	case QueryFailed               = 300;
	case NoSuitableConnector       = 301;
	case MaintenanceFreeConnection = 302;
	case ImpossibleDataProvider    = 303;
	case ConnectionRefused         = 304;
	case ServerDown                = 305;
	case StateNotEstablished       = 306;
	case ExtensionNotLoaded        = 307;
	case WrongDsnString            = 308;
	case UnknownSourceType         = 309;
	case DatabaseNameMissing       = 310;
	case SourceTypeNotDefined      = 311;
	case UnknownContractorName     = 312;
	case MissingArgumentDSN        = 313;
	case NoConfiguration           = 314;
	case ConnectionNotInit         = 315;
	case StopAutocommitFailure     = 316;
	case TimeoutNotChanged         = 317;
	case SetCharsetNameFailure     = 318;

	public function isFatal(): bool {
		return false;
	}
}
