<?php

declare(strict_types=1);
// SPDX-FileCopyrightText: 2025 Alec Kojaev <alec@kojaev.name>
// SPDX-License-Identifier: AGPL-3.0-or-later

use OCA\Calibre2OPDS\Calibre\Types\CalibreBookCriteria;
use OCA\Calibre2OPDS\Controller\OpdsController;
use OCA\Calibre2OPDS\Opds\OpdsResponse;
use OCA\Calibre2OPDS\Opds\OpenSearchResponse;
use OCA\Calibre2OPDS\Service\ICalibreService;
use OCA\Calibre2OPDS\Service\OpdsFeedService;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\Attribute\PublicPage;
use OCP\AppFramework\Http\Response;
use OCP\AppFramework\Http\StreamResponse;
use PHPUnit\Framework\TestCase;
use Stubs\L10NStub;
use Stubs\LoggerInterfaceStub;
use Stubs\SettingsServiceStub;
use Stubs\TestDataStub;

class OpdsControllerTest extends TestCase {
	use SettingsServiceStub;
	use L10NStub;
	use LoggerInterfaceStub;
	use TestDataStub;

	private OpdsController $controller;
	private object $calibreService;

	public function setUp(): void {
		$this->initSettingsService();
		$this->initL10N();
		$this->initLoggerInterface();
		$this->initTestData();
		$this->calibreService = $this->createStub(ICalibreService::class);
		$this->calibreService->method('getDatabase')->willReturn($this->dataDb);
		$this->controller = new OpdsController(
			$this->getMockBuilder(\OCP\IRequest::class)->disableOriginalConstructor()->getMock(),
			$this->calibreService,
			new OpdsFeedService($this->settings, $this->l),
			$this->settings,
			$this->l,
			$this->logger
		);
	}

	public function testAuth(): void {
		$this->loggedIn = false;
		$response = $this->controller->index();
		$this->assertNotNull($response, 'Null controller response');
		$this->assertEquals(Http::STATUS_UNAUTHORIZED, $response->getStatus(), 'Wrong HTTP status');
		/* NOTE: Headers cannot be tested since `Response` uses `\OC::$server` in `getHeaders()`.
		$headers = $response->getHeaders();
		$this->assertArrayHasKey('WWW-Authenticate', $headers, 'Missing WWW-Authenticate header');
		$this->assertEquals('Basic realm="Nextcloud authentication needed"', $headers['WWW-Authenticate'], 'Wrong WWW-Authenticate header');
		*/
	}

	public function testNullPath(): void {
		$this->loggedIn = true;
		$this->libraryFolder = null;
		$response = $this->controller->index();
		$this->assertNotNull($response, 'Null controller response');
		$this->assertEquals(Http::STATUS_NOT_FOUND, $response->getStatus(), 'Wrong HTTP status');
	}

	private static function loadXmlDoc(Response $response, string $defPrefix): SimpleXMLElement {
		$respText = $response->render();
		$xml = new SimpleXMLElement($respText);
		foreach ($xml->getDocNamespaces() as $prefix => $ns) {
			if ($prefix === '') {
				$prefix = $defPrefix;
			}
			$xml->registerXPathNamespace($prefix, $ns);
		}
		return $xml;
	}

	private function setUpDoc(): void {
		$this->loggedIn = true;
		$this->libraryFolder = $this->dataRoot;
	}

	public function testOpdsRoutesUseNativeSecurityAttributes(): void {
		foreach (['index', 'authors', 'authorPrefixes', 'publishers', 'languages', 'series', 'tags', 'books', 'searchXml', 'bookData', 'bookCover'] as $method) {
			$reflection = new ReflectionMethod(OpdsController::class, $method);
			$this->assertCount(1, $reflection->getAttributes(NoAdminRequired::class), $method);
			$this->assertCount(1, $reflection->getAttributes(NoCSRFRequired::class), $method);
			$this->assertCount(1, $reflection->getAttributes(PublicPage::class), $method);
		}
	}

	private function commonTestDoc($response, string $respClass = OpdsResponse::class): void {
		$this->assertNotNull($response, 'Null controller response');
		$this->assertEquals(Http::STATUS_OK, $response->getStatus(), 'Wrong HTTP status');
		$this->assertInstanceOf($respClass, $response, 'Wrong response class');
	}

	public function testException(): void {
		$this->setUpDoc();
		$this->calibreService->method('getDatabase')->willReturnCallback(function ($path) {
			throw new Exception('Test exception');
		});
		$this->expectMessage = [
			'level' => 'error',
			'msg' => 'Calibre OPDS request failed',
		];
		$response = $this->controller->index();
		$this->assertNotNull($response, 'Null controller response');
		$this->assertEquals(Http::STATUS_INTERNAL_SERVER_ERROR, $response->getStatus(), 'Wrong HTTP status');
	}

	public function testIndex(): void {
		$this->setUpDoc();
		$response = $this->controller->index();
		$this->commonTestDoc($response);
		$xml = self::loadXmlDoc($response, 'atom');
		$this->assertEquals('opds:index', $xml->xpath('atom:id')[0]);
		$this->assertEquals('app-route:index?', $xml->xpath('atom:link[@rel=\'self\']/@href')[0]);
		$this->assertContainsEquals('opds:authors', $xml->xpath('atom:entry/atom:id'));
		$this->assertContainsEquals('opds:publishers', $xml->xpath('atom:entry/atom:id'));
		$this->assertContainsEquals('opds:languages', $xml->xpath('atom:entry/atom:id'));
		$this->assertContainsEquals('opds:series', $xml->xpath('atom:entry/atom:id'));
		$this->assertContainsEquals('opds:tags', $xml->xpath('atom:entry/atom:id'));
		$this->assertContainsEquals('opds:books', $xml->xpath('atom:entry/atom:id'));
	}

	public function testAuthors(): void {
		$this->setUpDoc();

		$response = $this->controller->authors();
		$this->commonTestDoc($response);
		$xml = self::loadXmlDoc($response, 'atom');
		$this->assertEquals('opds:authors', $xml->xpath('atom:id')[0]);
		$this->assertEquals('app-route:authors?', $xml->xpath('atom:link[@rel=\'self\']/@href')[0]);
		// TODO: Test nav items

		$response = $this->controller->authors('A');
		$this->commonTestDoc($response);
		$xml = self::loadXmlDoc($response, 'atom');
		$this->assertEquals('opds:authors', $xml->xpath('atom:id')[0]);
		$this->assertEquals('app-route:authors?', $xml->xpath('atom:link[@rel=\'self\']/@href')[0]);
		// TODO: Test nav items
	}

	public function testAuthorPrefixes(): void {
		$this->setUpDoc();
		$response = $this->controller->authorPrefixes();
		$this->commonTestDoc($response);
		$xml = self::loadXmlDoc($response, 'atom');
		$this->assertEquals('opds:author_prefixes', $xml->xpath('atom:id')[0]);
		$this->assertEquals('app-route:author_prefixes?', $xml->xpath('atom:link[@rel=\'self\']/@href')[0]);
		// TODO: Test nav items
	}

	public function testPublishers(): void {
		$this->setUpDoc();
		$response = $this->controller->publishers();
		$this->commonTestDoc($response);
		$xml = self::loadXmlDoc($response, 'atom');
		$this->assertEquals('opds:publishers', $xml->xpath('atom:id')[0]);
		$this->assertEquals('app-route:publishers?', $xml->xpath('atom:link[@rel=\'self\']/@href')[0]);
		// TODO: Test nav items
	}

	public function testLanguages(): void {
		$this->setUpDoc();
		$response = $this->controller->languages();
		$this->commonTestDoc($response);
		$xml = self::loadXmlDoc($response, 'atom');
		$this->assertEquals('opds:languages', $xml->xpath('atom:id')[0]);
		$this->assertEquals('app-route:languages?', $xml->xpath('atom:link[@rel=\'self\']/@href')[0]);
		// TODO: Test nav items
	}

	public function testSeries(): void {
		$this->setUpDoc();
		$response = $this->controller->series();
		$this->commonTestDoc($response);
		$xml = self::loadXmlDoc($response, 'atom');
		$this->assertEquals('opds:series', $xml->xpath('atom:id')[0]);
		$this->assertEquals('app-route:series?', $xml->xpath('atom:link[@rel=\'self\']/@href')[0]);
		// TODO: Test nav items
	}

	public function testTags(): void {
		$this->setUpDoc();
		$response = $this->controller->tags();
		$this->commonTestDoc($response);
		$xml = self::loadXmlDoc($response, 'atom');
		$this->assertEquals('opds:tags', $xml->xpath('atom:id')[0]);
		$this->assertEquals('app-route:tags?', $xml->xpath('atom:link[@rel=\'self\']/@href')[0]);
		// TODO: Test nav items
	}

	public function testSearch(): void {
		$this->setUpDoc();
		$response = $this->controller->searchXml();
		$this->commonTestDoc($response, OpenSearchResponse::class);
		$xml = self::loadXmlDoc($response, 'os');
		$this->assertEquals('app-route:books?criterion=search&id={searchTerms}', $xml->xpath('os:Url/@template')[0]);
	}

	public function testBooks(): void {
		$this->setUpDoc();

		$response = $this->controller->books();
		$this->commonTestDoc($response);
		$xml = self::loadXmlDoc($response, 'atom');
		$this->assertEquals('opds:books', $xml->xpath('atom:id')[0]);
		$this->assertEquals('app-route:books?', $xml->xpath('atom:link[@rel=\'self\']/@href')[0]);
		// TODO: Test nav items, here and below

		$response = $this->controller->books(CalibreBookCriteria::SEARCH->value, 'a*');
		$this->commonTestDoc($response);
		$xml = self::loadXmlDoc($response, 'atom');
		$this->assertEquals('opds:books', $xml->xpath('atom:id')[0]);
		$this->assertEquals('app-route:books?', $xml->xpath('atom:link[@rel=\'self\']/@href')[0]);

		$response = $this->controller->books(CalibreBookCriteria::AUTHOR->value, '52');
		$this->commonTestDoc($response);
		$xml = self::loadXmlDoc($response, 'atom');
		$this->assertEquals('opds:books', $xml->xpath('atom:id')[0]);
		$this->assertEquals('app-route:books?', $xml->xpath('atom:link[@rel=\'self\']/@href')[0]);

		$response = $this->controller->books(CalibreBookCriteria::PUBLISHER->value, '91');
		$this->commonTestDoc($response);
		$xml = self::loadXmlDoc($response, 'atom');
		$this->assertEquals('opds:books', $xml->xpath('atom:id')[0]);
		$this->assertEquals('app-route:books?', $xml->xpath('atom:link[@rel=\'self\']/@href')[0]);

		$response = $this->controller->books(CalibreBookCriteria::LANGUAGE->value, '74');
		$this->commonTestDoc($response);
		$xml = self::loadXmlDoc($response, 'atom');
		$this->assertEquals('opds:books', $xml->xpath('atom:id')[0]);
		$this->assertEquals('app-route:books?', $xml->xpath('atom:link[@rel=\'self\']/@href')[0]);

		$response = $this->controller->books(CalibreBookCriteria::SERIES->value, '111');
		$this->commonTestDoc($response);
		$xml = self::loadXmlDoc($response, 'atom');
		$this->assertEquals('opds:books', $xml->xpath('atom:id')[0]);
		$this->assertEquals('app-route:books?', $xml->xpath('atom:link[@rel=\'self\']/@href')[0]);

		$response = $this->controller->books(CalibreBookCriteria::TAG->value, '132');
		$this->commonTestDoc($response);
		$xml = self::loadXmlDoc($response, 'atom');
		$this->assertEquals('opds:books', $xml->xpath('atom:id')[0]);
		$this->assertEquals('app-route:books?', $xml->xpath('atom:link[@rel=\'self\']/@href')[0]);
	}

	public function testBookData(): void {
		$this->setUpDoc();

		$response = $this->controller->bookData('12', 'EPUB');
		$this->commonTestDoc($response, StreamResponse::class);

		$response = $this->controller->bookData('12', 'FB2');
		$this->assertNotNull($response, 'Null controller response');
		$this->assertEquals(Http::STATUS_NOT_FOUND, $response->getStatus(), 'Wrong HTTP status');

		$response = $this->controller->bookData('12', '../EPUB');
		$this->assertEquals(Http::STATUS_NOT_FOUND, $response->getStatus(), 'Unsafe format was accepted');

		$response = $this->controller->bookData('12', 'XXX');
		$this->assertNotNull($response, 'Null controller response');
		$this->assertEquals(Http::STATUS_NOT_FOUND, $response->getStatus(), 'Wrong HTTP status');
	}

	public function testBookCover(): void {
		$this->setUpDoc();

		$response = $this->controller->bookCover('12');
		$this->commonTestDoc($response, StreamResponse::class);

		$response = $this->controller->bookCover('13');
		$this->assertNotNull($response, 'Null controller response');
		$this->assertEquals(Http::STATUS_NOT_FOUND, $response->getStatus(), 'Wrong HTTP status');

		$response = $this->controller->bookCover('14');
		$this->assertNotNull($response, 'Null controller response');
		$this->assertEquals(Http::STATUS_NOT_FOUND, $response->getStatus(), 'Wrong HTTP status');

		$response = $this->controller->bookCover('999');
		$this->assertNotNull($response, 'Null controller response');
		$this->assertEquals(Http::STATUS_NOT_FOUND, $response->getStatus(), 'Wrong HTTP status');
	}
}
