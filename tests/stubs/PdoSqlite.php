<?php

declare(strict_types=1);
// SPDX-FileCopyrightText: 2026 Calibre2OPDS contributors
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Pdo;

use PDO;

/**
 * Psalm model for the PHP 8.5 PDO SQLite driver subclass.
 *
 * This file is loaded only by Psalm when analysis runs on older PHP versions.
 */
class Sqlite extends PDO {
	public const ATTR_OPEN_FLAGS = 0;
	public const OPEN_READONLY = 1;
	public const DETERMINISTIC = 2048;

	public function createFunction(
		string $function,
		callable $callback,
		int $numArgs = -1,
		int $flags = 0,
	): bool {
	}
}
