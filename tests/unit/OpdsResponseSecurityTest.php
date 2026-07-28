<?php

declare(strict_types=1);
// SPDX-FileCopyrightText: 2026 Calibre2OPDS contributors
// SPDX-License-Identifier: AGPL-3.0-or-later

use OCA\Calibre2OPDS\Opds\OpdsApp;
use OCA\Calibre2OPDS\Opds\OpdsEntry;
use OCA\Calibre2OPDS\Opds\OpdsLink;
use OCA\Calibre2OPDS\Opds\OpdsResponse;
use PHPUnit\Framework\TestCase;

final class OpdsResponseSecurityTest extends TestCase {
	public function testMetadataAndLinksAreXmlEscaped(): void {
		$response = new OpdsResponse(
			new OpdsApp('calibre_opds', 'Calibre ]]> & OPDS', '0.0.7', 'https://example.test/?a=1&b=2'),
			'books:日本語 & "quotes"',
			'日本語 & "quotes" <catalogue>'
		);
		$response->addLink(new OpdsLink(
			'self',
			"https://example.test/books?q=fish&author=\"O'Brien\"",
			OpdsResponse::MIME_TYPE_ATOM
		));
		$response->addEntry(new OpdsEntry(
			'book:1&2',
			'Rock & Roll <日本語>',
			"Description with ]]> and \"quotes\" & apostrophes'"
		));

		$rendered = $response->render();
		$xml = new SimpleXMLElement($rendered);

		$this->assertSame('日本語 & "quotes" <catalogue>', (string)$xml->title);
		$this->assertSame('Rock & Roll <日本語>', (string)$xml->entry->title);
		$this->assertSame("Description with ]]> and \"quotes\" & apostrophes'", (string)$xml->entry->content);
		$this->assertStringContainsString("q=fish&amp;author=&quot;O'Brien&quot;", $rendered);
	}
}
