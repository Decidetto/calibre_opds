<?php

declare(strict_types=1);
// SPDX-FileCopyrightText: 2026 Calibre2OPDS contributors
// SPDX-License-Identifier: AGPL-3.0-or-later

use OCA\Calibre2OPDS\Calibre\CalibreDB;
use OCP\Files\Folder;
use PHPUnit\Framework\TestCase;
use Stubs\StorageStub;

final class CalibreDBReadOnlyTest extends TestCase {
	use StorageStub;

	private string $tempDirectory;

	protected function setUp(): void {
		$this->tempDirectory = sys_get_temp_dir() . '/calibre-opds-' . bin2hex(random_bytes(8));
		$this->assertTrue(mkdir($this->tempDirectory, 0700));
	}

	protected function tearDown(): void {
		foreach (glob($this->tempDirectory . '/*') ?: [] as $path) {
			if (is_file($path)) {
				unlink($path);
			}
		}
		rmdir($this->tempDirectory);
	}

	private function createRoot(string $databasePath): Folder {
		$this->initStorage($databasePath, true);
		return $this->createFolderNode('library', [
			$this->createFileNode('metadata.db'),
		]);
	}

	public function testReadOnlyDatabaseDoesNotChangeLibrary(): void {
		$databasePath = $this->tempDirectory . '/metadata.db';
		$options = [
			PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
		];
		if (class_exists(\Pdo\Sqlite::class)) {
			$database = new \Pdo\Sqlite('sqlite:' . $databasePath, null, null, $options);
		} else {
			$database = new PDO('sqlite:' . $databasePath, null, null, $options);
		}
		$schema = file_get_contents(__DIR__ . '/../files/metadata.sql');
		$this->assertIsString($schema);
		if ($database instanceof \Pdo\Sqlite) {
			$database->createFunction('title_sort', static fn (string $name): string => $name, 1);
			$database->createFunction('uuid4', static fn (): string => '00000000-0000-4000-8000-000000000000', 0);
		} else {
			$database->sqliteCreateFunction('title_sort', static fn (string $name): string => $name, 1);
			$database->sqliteCreateFunction('uuid4', static fn (): string => '00000000-0000-4000-8000-000000000000', 0);
		}
		$database->exec($schema);
		$database = null;

		$before = [
			'files' => scandir($this->tempDirectory),
			'hash' => hash_file('sha256', $databasePath),
			'mtime' => filemtime($databasePath),
		];

		$calibre = CalibreDB::fromFolder($this->createRoot($databasePath));
		$this->assertNotNull($calibre->querySingle('select count(*) as count from books'));
		try {
			$calibre->getDatabase()->exec('create table forbidden_write (id integer)');
			$this->fail('Read-only database accepted a write');
		} catch (PDOException) {
			$this->addToAssertionCount(1);
		}
		unset($calibre);

		clearstatcache(true, $databasePath);
		$this->assertSame($before['files'], scandir($this->tempDirectory));
		$this->assertSame($before['hash'], hash_file('sha256', $databasePath));
		$this->assertSame($before['mtime'], filemtime($databasePath));
	}

	public function testUnsupportedSchemaIsRejected(): void {
		$databasePath = $this->tempDirectory . '/metadata.db';
		$database = new PDO('sqlite:' . $databasePath);
		$database->exec('create table books (id integer)');
		$database = null;

		$this->expectException(PDOException::class);
		$this->expectExceptionMessage('Unsupported Calibre database schema');
		CalibreDB::fromFolder($this->createRoot($databasePath));
	}

	public function testMalformedDatabaseIsRejected(): void {
		$databasePath = $this->tempDirectory . '/metadata.db';
		file_put_contents($databasePath, 'not a sqlite database');

		$this->expectException(PDOException::class);
		CalibreDB::fromFolder($this->createRoot($databasePath));
	}
}
