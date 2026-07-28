<?php

declare(strict_types=1);
// SPDX-FileCopyrightText: 2023 Alec Kojaev <alec@kojaev.name>
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OCA\Calibre2OPDS\Calibre\Types;

use OCA\Calibre2OPDS\Calibre\CalibreItem;
use OCA\Calibre2OPDS\Calibre\ICalibreDB;
use OCA\Calibre2OPDS\Util\LibraryPath;
use OCA\Calibre2OPDS\Util\MapAggregate;
use OCP\Files\File;
use OCP\Files\FileInfo;
use OCP\Files\Folder;
use OCP\Files\InvalidPathException;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;
use PDOException;
use Traversable;

/**
 * Class for Calibre book data entry.
 *
 * @property string $path
 * @property string $name
 * @property string $format
 */
final class CalibreBookFormat extends CalibreItem {
	public const URI = 'book-format';

	/**
	 * SQL statement to extract book data.
	 *
	 * This is a format, with parameter 1 containing WHERE clause.
	 *
	 * @var string
	 */
	private const SQL_BOOK_DATA = <<<'EOT'
		select books.path as path, data.name as name, data.format as format
		from data left join books on books.id = data.book
		%1$s
	EOT;

	private function __construct(ICalibreDB $db, array $data) {
		parent::__construct($db, $data);
	}

	/**
	 * Get book data file.
	 *
	 * @param Folder $root root library folder.
	 *
	 * @return File|null data file, or `null` if doesn't exist.
	 */
	public function getDataFile(Folder $root): ?File {
		try {
			$filename = $this->name . '.' . strtolower($this->format);
			$data = $root->get(LibraryPath::join($this->path, $filename));
		} catch (\UnexpectedValueException|InvalidPathException|NotFoundException|NotPermittedException $e) {
			return null;
		}
		if (!$data->isReadable() || $data->getType() !== FileInfo::TYPE_FILE || !($data instanceof File)) {
			return null;
		}
		return $data;
	}

	/**
	 * Get available data formats for a book.
	 *
	 * @param ICalibreDB $db Calibre database.
	 * @param mixed $book_id book ID.
	 *
	 * @return Traversable<self> list of book data entries.
	 * @throws PDOException on error.
	 */
	public static function getByBook(ICalibreDB $db, $book_id): Traversable {
		return new MapAggregate(
			$db->query(sprintf(self::SQL_BOOK_DATA, 'where books.id = ?'), [$book_id]),
			fn (array $row) => new self($db, $row)
		);
	}


	/**
	 * Get specific data format for a book.
	 *
	 * @param ICalibreDB $db Calibre database.
	 * @param mixed $book_id book ID.
	 * @param string $type format name.
	 *
	 * @return self|null book data entry, or `null` if not found.
	 * @throws PDOException on error.
	 */
	public static function getByBookAndType(ICalibreDB $db, $book_id, string $type): ?self {
		$data = $db->querySingle(sprintf(self::SQL_BOOK_DATA, 'where books.id = ? and data.format = ?'), [$book_id, strtoupper($type)]);
		return is_null($data) ? null : new self($db, $data);
	}
}
