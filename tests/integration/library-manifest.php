<?php

declare(strict_types=1);
// SPDX-FileCopyrightText: 2026 Calibre2OPDS contributors
// SPDX-License-Identifier: AGPL-3.0-or-later

if ($argc !== 2 || !is_dir($argv[1])) {
	fwrite(STDERR, "Usage: library-manifest.php LIBRARY_DIRECTORY\n");
	exit(2);
}

$root = rtrim($argv[1], '/');
$rows = [];
$iterator = new RecursiveIteratorIterator(
	new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
	RecursiveIteratorIterator::SELF_FIRST
);
foreach ($iterator as $item) {
	$path = substr($item->getPathname(), strlen($root) + 1);
	$rows[] = [
		'path' => str_replace(DIRECTORY_SEPARATOR, '/', $path),
		'type' => $item->isDir() ? 'directory' : 'file',
		'size' => $item->isDir() ? null : $item->getSize(),
		'mtime' => $item->getMTime(),
		'sha256' => $item->isDir() ? null : hash_file('sha256', $item->getPathname()),
	];
}
usort($rows, static fn (array $a, array $b): int => strcmp($a['path'], $b['path']));
echo json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL;
