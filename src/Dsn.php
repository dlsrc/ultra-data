<?php declare(strict_types=1);

namespace Ultra\Data;

use Ultra\Fail;
use Ultra\Result;
use Ultra\State;

final class Dsn {
	private string $_dsn;

	public function __construct(string $dsn) {
		$this->_dsn = trim($dsn);
	}

	public function parse(): State {
		$source = [];

		if (str_contains($this->_dsn, '://')) {
			$source = parse_url($this->_dsn);

			if (!isset($source['scheme'])) {
				return new Fail(Status::WrongDsnString, 'Incorrect Dsn string', __FILE__, __LINE__);
			}

			if (!in_array($source['scheme'], ['mysql', 'mariadb', 'pgsql', 'sqlite'])) {
				return new Fail(Status::UnknownSourceType, 'Unknown source type: "'.$source['scheme'].'"', __FILE__, __LINE__);
			}

			$source['type'] = $source['scheme'];
			unset($source['scheme']);

			if ('sqlite' == $source['type']) {
				if (isset($source['path'])) {
					if (!isset($source['host'])) {
						return new Fail(Status::WrongDsnString, 'Incorrect Dsn string', __FILE__, __LINE__);
					}

					switch ($source['host']) {
					case '~':
						if ('cli' == PHP_SAPI) {
							$source['dbname'] = '~'.$source['path'];
						}
						else {
							$source['dbname'] = $_SERVER['DOCUMENT_ROOT'].$source['path'];
						}

						break;
					case '..':
						$source['dbname'] = dirname($_SERVER['SCRIPT_FILENAME']).'/..'.$source['path'];
						break;
					case '.':
						$source['dbname'] = dirname($_SERVER['SCRIPT_FILENAME']).$source['path'];
						break;
					default:
						$source['dbname'] = '/'.$source['host'].$source['path'];
					}

					unset($source['path'], $source['host']);
				}
				elseif (isset($source['host'])) {
					$source['dbname'] = $source['host'];
					unset($source['host']);
				}
				else {
					return new Fail(Status::WrongDsnString, 'Incorrect Dsn string', __FILE__, __LINE__);
				}

				if (!isset($source['dbname'])) {
					return new Fail(Status::DatabaseNameMissing, 'SQLite database name is missing.', __FILE__, __LINE__);
				}
			}
			else {
				if (isset($source['path'])) {
					$path = preg_split('/\//', $source['path'], -1, PREG_SPLIT_NO_EMPTY);

					$source['dbname'] = $path[0];

					if (isset($path[1])) {
						if ('pgsql' == $source['type']) {
							$source['schema'] = $path[1];
						}
						else {
							$source['prefix'] = $path[1];
						}
					}

					unset($source['path']);
				}

				if (isset($source['query'])) {
					parse_str($source['query'], $query);
					unset($source['query']);
					$source = $source + $query;
				}		
			}
		}
		else {
			parse_str(implode('&', preg_split('/[;&\s]+/', $this->_dsn, -1, PREG_SPLIT_NO_EMPTY)), $source);

			if (!isset($source['type'])) {
				if (!isset($source['port'])) {
					return new Fail(Status::SourceTypeNotDefined, 'Source type not defined.', __FILE__, __LINE__);
				}

				switch ($source['port']) {
				case 3306:
					$source['type'] = 'mysql';
					break;
				case 5432:
					$source['type'] = 'pgsql';
					break;
				default:
					return new Fail(Status::SourceTypeNotDefined, 'Source type not defined.', __FILE__, __LINE__);
				}
			}
			elseif (!isset($source['port']) && 'sqlite' != $source['type']) {
				switch ($source['type']) {
				case 'mariadb':
				case 'mysql':
					$source['port'] = '3306';
					break;
				case 'pgsql':
					$source['port'] = '5432';
					break;
				default:
					return new Fail(Status::SourceTypeNotDefined, 'Source type not defined.', __FILE__, __LINE__);
				}
			}
			elseif ('sqlite' == $source['type'] && !isset($source['dbname'])) {
				return new Fail(Status::DatabaseNameMissing, 'SQLite database name is missing.', __FILE__, __LINE__);
			}
		}

		if ('sqlite' != $source['type']) {
			$source['port'] = (string) $source['port'];
		}

		return new Result($source);
	}
}
