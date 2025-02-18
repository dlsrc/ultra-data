<?php declare(strict_types=1);
/**
 * (c) 2005-2025 Dmitry Lebedev <dl@adios.ru>
 * This source code is part of the Ultra data package.
 * Please see the LICENSE file for copyright and licensing information.
 */
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
			$source = parse_url(str_replace(':///', '://', $this->_dsn));

			if (!isset($source['scheme'])) {
				return new Fail(Status::WrongDsnString, 'Incorrect Dsn string: '.$this->_dsn, __FILE__, __LINE__);
			}

			$source['type'] = strtolower($source['scheme']);
			unset($source['scheme']);

			if (null == Type::tryFrom($source['type'])) {
				return new Fail(Status::UnknownSourceType, 'Unknown source type: "'.$source['type'].'"', __FILE__, __LINE__);
			}

			if ('sqlite' == $source['type']) {
				if (isset($source['path'])) {
					if (!isset($source['host'])) {
						return new Fail(Status::WrongDsnString, 'Incorrect Dsn string: '.$this->_dsn, __FILE__, __LINE__);
					}

					$source['dbname'] = match ($source['host']) {
						'~' => ('cli' == PHP_SAPI) ? '~'.$source['path'] : $_SERVER['DOCUMENT_ROOT'].$source['path'],
						'..' => dirname($_SERVER['SCRIPT_FILENAME']).'/..'.$source['path'],
						'.' => dirname($_SERVER['SCRIPT_FILENAME']).$source['path'],
						default => ('Windows' == PHP_OS_FAMILY) ? $source['host'].':'.$source['path'] : '/'.$source['host'].$source['path'],
					};

					unset($source['path'], $source['host']);
				}
				elseif (isset($source['host'])) {
					$source['dbname'] = $source['host'];
					unset($source['host']);
				}
				else {
					return new Fail(Status::WrongDsnString, 'Incorrect Dsn string: '.$this->_dsn, __FILE__, __LINE__);
				}

				if (!isset($source['dbname'])) {
					return new Fail(Status::DatabaseNameMissing, 'SQLite database name is missing.', __FILE__, __LINE__);
				}

				if (isset($source['query'])) {
					parse_str($source['query'], $query);
					unset($source['query']);
					$source = $source + $query;
				}
			}
			else {
				if (isset($source['path'])) {
					$path = preg_split('/\//', $source['path'], -1, PREG_SPLIT_NO_EMPTY);

					$source['dbname'] = array_shift($path);

					if (isset($path[0])) {
						if ('pgsql' == $source['type']) {
							$source['schema'] = $path[0];
						}
						elseif ('localhost' == $source['host']) {
							$source['socket'] = '/'.implode('/', $path);
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

			if (isset($source['type'])) {
				$source['type'] = strtolower($source['type']);
			}

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
			elseif (null == Type::tryFrom($source['type'])) {
				return new Fail(Status::UnknownSourceType, 'Unknown source type: "'.$source['type'].'"', __FILE__, __LINE__);
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

		if (isset($source['port'])) {
			$source['port'] = (string) $source['port'];
		}

		return new Result($source);
	}
}
