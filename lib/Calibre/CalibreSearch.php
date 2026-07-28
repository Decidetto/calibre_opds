<?php

declare(strict_types=1);
// SPDX-FileCopyrightText: 2023 Alec Kojaev <alec@kojaev.name>
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OCA\Calibre2OPDS\Calibre;

use Normalizer;
use OCA\Calibre2OPDS\Calibre\Types\CalibreBook;

/**
 * Search implementation.
 */
final class CalibreSearch {
	/**
	 * Default Unicode form used for search.
	 */
	private const DEFAULT_FORM = Normalizer::NFKC;

	/**
	 * Construct an instance.
	 *
	 * @param non-empty-string $pattern search pattern to look for.
	 */
	private function __construct(
		private string $pattern,
	) {
	}

	/**
	 * Remove diacritics from the text.
	 *
	 * @param string $text text to process.
	 * @return string text without diacritics.
	 */
	private static function removeDiacritics(string $text): string {
		$result = normalizer_normalize($text, Normalizer::NFD);
		if ($result === false) {
			return $text;
		}
		$result = preg_replace('/[\p{M}]/u', '', $result);
		if (is_null($result)) {
			return $text;
		}
		$result = normalizer_normalize($result, self::DEFAULT_FORM);
		return ($result === false) ? $text : $result;
	}

	/**
	 * Append a string to strings to search.
	 *
	 * This method performs all necessary transformations to guarantee search results.
	 *
	 * @param array<string> &$haystack array to add string to.
	 * @param mixed $text text to add.
	 */
	private static function appendHaystack(array &$haystack, mixed $text): void {
		if (!is_string($text) || $text === '') {
			return;
		}
		$textNorm = normalizer_normalize($text, self::DEFAULT_FORM);
		if ($textNorm === false) {
			$textNorm = $text;
		}
		array_push($haystack, $textNorm);
		$textNoMarks = self::removeDiacritics($text);
		if ($textNoMarks !== $text) {
			// Search with diacritics removed
			array_push($haystack, $textNoMarks);
		}
	}

	/**
	 * Filter for books.
	 *
	 * @param CalibreBook $item book item to check.
	 * @return bool `true` if the book passes, `false` otherwise.
	 */
	private function filterBook(CalibreBook $item): bool {
		$haystack = [];
		self::appendHaystack($haystack, $item->title);
		self::appendHaystack($haystack, $item->comment);
		foreach ($item->authors as $author) {
			self::appendHaystack($haystack, $author->name);
		}
		foreach ($item->series as $series) {
			self::appendHaystack($haystack, $series->name);
		}
		foreach ($item->tags as $tag) {
			self::appendHaystack($haystack, $tag->name);
		}
		$match = @preg_grep($this->pattern, $haystack);
		/** @psalm-suppress RedundantConditionGivenDocblockType -- see <https://github.com/vimeo/psalm/issues/9543> */
		return $match !== false && count($match) > 0;
	}

	/**
	 * Create a pattern from search string.
	 *
	 * @param string $terms search string.
	 * @return non-empty-string|null search pattern, or `null` if not created.
	 */
	private static function createPattern(string $terms): ?string {
		if ($terms === '') {
			return null;
		}
		$dataNorm = normalizer_normalize($terms, self::DEFAULT_FORM);
		if ($dataNorm === false) {
			$dataNorm = $terms;
		}
		$dataEsc = str_replace('/', '\/', $dataNorm);
		$pattern = '/(*LIMIT_MATCH=100000)(*LIMIT_DEPTH=1000)' . $dataEsc . '/inu';
		if (@preg_match($pattern, '') === false) {
			// Preserve a bounded search response for malformed client patterns.
			return '/' . preg_quote($dataNorm, '/') . '/inu';
		}
		return $pattern;
	}

	/**
	 * Get a callable filter for search among books.
	 *
	 * @param string $terms search string.
	 * @return callable(CalibreBook):bool|null callable filter for books, or `null` to skip checks.
	 */
	public static function searchBooks(string $terms): ?callable {
		$pattern = self::createPattern($terms);
		if (is_null($pattern)) {
			return null;
		}
		$obj = new self($pattern);
		return $obj->filterBook(...);
	}
}
