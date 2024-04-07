<?php declare(strict_types=1);
/**
 * (c) 2005-2024 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Ultra data package.
 * Please see the LICENSE file for copyright and licensing information.
 */
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
	case NoConfigurationByName     = 314;
	case NoConfigurationByType     = 315;
	case ConnectionNotInit         = 316;
	case StopAutocommitFailure     = 317;
	case TimeoutNotChanged         = 318;
	case SetCharsetNameFailure     = 319;
	case SqliteDbMakeDirError      = 320;

	public function isFatal(): bool {
		return false;
	}
}
