<?php

declare(strict_types=1);
// SPDX-FileCopyrightText: 2023 Alec Kojaev <alec@kojaev.name>
// SPDX-License-Identifier: AGPL-3.0-or-later

use OCA\Calibre2OPDS\Calibre\CalibreSearch;
use OCA\Calibre2OPDS\Calibre\ICalibreDB;
use OCA\Calibre2OPDS\Calibre\Types\CalibreBook;
use PHPUnit\Framework\TestCase;
use Stubs\CalibreStub;

class SearchTest extends TestCase {
	private const BOOK_DEFAULTS = [
		'id' => 0,
		'series_index' => 1.0,
		'last_modified' => null,
		'pubdate' => null,
		'timestamp' => null,
		'has_cover' => 0,
		'title' => '',
		'comment' => null,
	];

	use CalibreStub;

	private ICalibreDB $db;

	public function setUp(): void {
		$this->db = $this->createStub(ICalibreDB::class);
	}

	public function testEmpty(): void {
		$filter = CalibreSearch::searchBooks('');
		$this->assertNull($filter, 'Empty search');
	}

	public function testSimple(): void {
		$filter = CalibreSearch::searchBooks('simple');
		$this->assertFalse(call_user_func($filter, $this->createCalibreItem(CalibreBook::class, $this->db, [
			...self::BOOK_DEFAULTS,
			'title' => 'This is a test',
		])), 'Simple search in title -- no match');
		$this->assertTrue(call_user_func($filter, $this->createCalibreItem(CalibreBook::class, $this->db, [
			...self::BOOK_DEFAULTS,
			'title' => 'This is a simple test',
		])), 'Simple search in title -- literal');
		$this->assertTrue(call_user_func($filter, $this->createCalibreItem(CalibreBook::class, $this->db, [
			...self::BOOK_DEFAULTS,
			'title' => 'This is a Simple Test',
		])), 'Simple search in title -- case-insensitive');
		$this->assertTrue(call_user_func($filter, $this->createCalibreItem(CalibreBook::class, $this->db, [
			...self::BOOK_DEFAULTS,
			'title' => 'This is a Símple Tést',
		])), 'Simple search in title -- diacritics-insensitive');
		$filter = CalibreSearch::searchBooks(normalizer_normalize('símple', Normalizer::NFC));
		$this->assertTrue(call_user_func($filter, $this->createCalibreItem(CalibreBook::class, $this->db, [
			...self::BOOK_DEFAULTS,
			'title' => 'This is a Símple Tést',
		])), 'Simple search in title -- diacritics-insensitive (NFC)');
		$filter = CalibreSearch::searchBooks(normalizer_normalize('símple', Normalizer::NFD));
		$this->assertTrue(call_user_func($filter, $this->createCalibreItem(CalibreBook::class, $this->db, [
			...self::BOOK_DEFAULTS,
			'title' => 'This is a Símple Tést',
		])), 'Simple search in title -- diacritics-insensitive (NFD)');
	}

	public function testRegex(): void {
		$filter = CalibreSearch::searchBooks('[AZ][0-9]+');
		$this->assertFalse(call_user_func($filter, $this->createCalibreItem(CalibreBook::class, $this->db, [
			...self::BOOK_DEFAULTS,
			'title' => 'This is a test: N123',
		])), 'Regex search in title -- no match (1)');
		$this->assertFalse(call_user_func($filter, $this->createCalibreItem(CalibreBook::class, $this->db, [
			...self::BOOK_DEFAULTS,
			'title' => 'This is a test: A',
		])), 'Regex search in title -- no match (2)');
		$this->assertTrue(call_user_func($filter, $this->createCalibreItem(CalibreBook::class, $this->db, [
			...self::BOOK_DEFAULTS,
			'title' => 'This is a test: A1',
		])), 'Regex search in title -- match (1)');
		$this->assertTrue(call_user_func($filter, $this->createCalibreItem(CalibreBook::class, $this->db, [
			...self::BOOK_DEFAULTS,
			'title' => 'This is a test: Z123',
		])), 'Regex search in title -- match (2)');
	}
	public function testMalformedRegexFallsBackToLiteralSearch(): void {
		$filter = CalibreSearch::searchBooks('[/');
		$this->assertTrue(call_user_func($filter, $this->createCalibreItem(CalibreBook::class, $this->db, [
			...self::BOOK_DEFAULTS,
			'title' => 'This contains a [/ literal',
		])));
	}

}
