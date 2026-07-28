<?php

declare(strict_types=1);
// SPDX-FileCopyrightText: 2026 Calibre2OPDS contributors
// SPDX-License-Identifier: AGPL-3.0-or-later

if ($argc !== 2 || $argv[1] === '' || is_dir($argv[1])) {
	fwrite(STDERR, "Usage: create-library.php NEW_LIBRARY_DIRECTORY\n");
	exit(2);
}

$root = $argv[1];
if (!mkdir($root, 0750, true) && !is_dir($root)) {
	throw new RuntimeException('Unable to create integration library');
}

$schema = file_get_contents(__DIR__ . '/../files/metadata.sql');
$data = file_get_contents(__DIR__ . '/integration-data.sql');
if ($schema === false || $data === false) {
	throw new RuntimeException('Unable to load integration schema');
}

$options = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION];
if (class_exists(\Pdo\Sqlite::class)) {
	$database = new \Pdo\Sqlite('sqlite:' . $root . '/metadata.db', null, null, $options);
	$database->createFunction('title_sort', static fn (string $name): string => $name, 1);
	$database->createFunction('uuid4', static fn (): string => '00000000-0000-4000-8000-000000000000', 0);
} else {
	$database = new PDO('sqlite:' . $root . '/metadata.db', null, null, $options);
	$database->sqliteCreateFunction('title_sort', static fn (string $name): string => $name, 1);
	$database->sqliteCreateFunction('uuid4', static fn (): string => '00000000-0000-4000-8000-000000000000', 0);
}
$database->exec($schema);
$database->exec($data);
$database = null;

$bookPath = $root . '/Ada Lovelace/Unicode & "Quotes" — 日本語 (1)';
$missingPath = $root . '/Anonymous/Missing fields (2)';
if (!mkdir($bookPath, 0750, true) || !mkdir($missingPath, 0750, true)) {
	throw new RuntimeException('Unable to create integration book directories');
}

$epubPath = $bookPath . '/Unicode & "Quotes" — 日本語.epub';
$epub = new ZipArchive();
if ($epub->open($epubPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
	throw new RuntimeException('Unable to create integration EPUB');
}
$epub->addFromString('mimetype', 'application/epub+zip');
$epub->setCompressionName('mimetype', ZipArchive::CM_STORE);
$epub->addFromString('META-INF/container.xml', <<<'XML'
<?xml version="1.0"?>
<container version="1.0" xmlns="urn:oasis:names:tc:opendocument:xmlns:container">
  <rootfiles>
    <rootfile full-path="OEBPS/content.opf" media-type="application/oebps-package+xml"/>
  </rootfiles>
</container>
XML);
$epub->addFromString('OEBPS/content.opf', <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<package version="3.0" xmlns="http://www.idpf.org/2007/opf" unique-identifier="book-id">
  <metadata xmlns:dc="http://purl.org/dc/elements/1.1/">
    <dc:identifier id="book-id">urn:uuid:00000000-0000-4000-8000-000000000001</dc:identifier>
    <dc:title>Unicode &amp; "Quotes" — 日本語</dc:title>
    <dc:language>en</dc:language>
  </metadata>
  <manifest/>
  <spine/>
</package>
XML);
$epub->close();

$pdf = <<<'PDF'
%PDF-1.4
1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj
2 0 obj<</Type/Pages/Count 0/Kids[]>>endobj
trailer<</Root 1 0 R>>
%%EOF
PDF;
file_put_contents($bookPath . '/Unicode & "Quotes" — 日本語.pdf', $pdf);

$cover = base64_decode(
	'/9j/4AAQSkZJRgABAQEASABIAAD/2wBDAP//////////////////////////////////////////////////////////////////////////////////////'
	. '2wBDAf//////////////////////////////////////////////////////////////////////////////////////'
	. 'wAARCAABAAEDASIAAhEBAxEB/8QAFQABAQAAAAAAAAAAAAAAAAAAAAX/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/'
	. '9oADAMBAAIQAxAAAAEf/8QAFBABAAAAAAAAAAAAAAAAAAAAAP/aAAgBAQABBQJ//8QAFBEBAAAAAAAAAAAAAAAA'
	. 'AAAAAP/aAAgBAwEBPwF//8QAFBEBAAAAAAAAAAAAAAAAAAAAAP/aAAgBAgEBPwF//8QAFBABAAAAAAAAAAAAAAAA'
	. 'AAAAAP/aAAgBAQAGPwJ//8QAFBABAAAAAAAAAAAAAAAAAAAAAP/aAAgBAQABPyF//9oADAMBAAIAAwAAABAf/8QA'
	. 'FBEBAAAAAAAAAAAAAAAAAAAAAP/aAAgBAwEBPxB//8QAFBEBAAAAAAAAAAAAAAAAAAAAAP/aAAgBAgEBPxB//8QA'
	. 'FBABAQAAAAAAAAAAAAAAAAAAARD/2gAIAQEAAT8Qh//Z',
	true
);
if ($cover === false) {
	throw new RuntimeException('Unable to decode integration cover');
}
file_put_contents($bookPath . '/cover.jpg', $cover);

$iterator = new RecursiveIteratorIterator(
	new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
	RecursiveIteratorIterator::SELF_FIRST
);
foreach ($iterator as $item) {
	touch($item->getPathname(), 1767225600);
}
touch($root, 1767225600);
