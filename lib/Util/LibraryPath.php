<?php

declare(strict_types=1);
// SPDX-FileCopyrightText: 2026 Calibre2OPDS contributors
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OCA\Calibre2OPDS\Util;

use UnexpectedValueException;

/**
 * Validates paths before they cross into a user's Nextcloud Files namespace.
 */
final class LibraryPath {
	/**
	 * Return a canonical relative path or fail closed.
	 *
	 * @throws UnexpectedValueException if the path is empty, absolute, or contains traversal.
	 */
	public static function normalize(string $path): string {
		if ($path === ''
			|| str_starts_with($path, '/')
			|| str_contains($path, "\0")
			|| str_contains($path, '\\')) {
			throw new UnexpectedValueException('The library path must be a relative Nextcloud Files path');
		}

		$segments = explode('/', $path);
		foreach ($segments as $segment) {
			if ($segment === '' || $segment === '.' || $segment === '..') {
				throw new UnexpectedValueException('The library path contains an unsafe segment');
			}
		}

		return implode('/', $segments);
	}

	/**
	 * Join trusted relative path components and validate the result.
	 *
	 * @throws UnexpectedValueException if any component could escape the library.
	 */
	public static function join(string ...$parts): string {
		return self::normalize(implode('/', $parts));
	}

	private function __construct() {
	}
}
