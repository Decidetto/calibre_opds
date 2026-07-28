<?php

declare(strict_types=1);
// SPDX-FileCopyrightText: 2026 Calibre2OPDS contributors
// SPDX-License-Identifier: AGPL-3.0-or-later

use OCA\Calibre2OPDS\Util\LibraryPath;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class LibraryPathTest extends TestCase {
	public function testAcceptsNestedUnicodePath(): void {
		$this->assertSame("Books/Calibre/日本語 & quotes ' work", LibraryPath::normalize("Books/Calibre/日本語 & quotes ' work"));
	}

	public function testJoinsSafeBookPath(): void {
		$this->assertSame(
			'Author Name/Book & Title/book name.epub',
			LibraryPath::join('Author Name/Book & Title', 'book name.epub')
		);
	}

	/**
	 * @return array<string,array{string}>
	 */
	public static function unsafePathProvider(): array {
		return [
			'empty' => [''],
			'absolute' => ['/Books/Calibre'],
			'parent traversal' => ['Books/../Other'],
			'current directory' => ['Books/./Calibre'],
			'empty segment' => ['Books//Calibre'],
			'trailing slash' => ['Books/Calibre/'],
			'backslash' => ['Books\Calibre'],
			'nul byte' => ["Books/Calibre\0/Other"],
		];
	}

	#[DataProvider('unsafePathProvider')]
	public function testRejectsUnsafePath(string $path): void {
		$this->expectException(UnexpectedValueException::class);
		LibraryPath::normalize($path);
	}
}
