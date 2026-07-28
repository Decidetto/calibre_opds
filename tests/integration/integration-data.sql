-- SPDX-FileCopyrightText: 2026 Calibre2OPDS contributors
-- SPDX-License-Identifier: CC0-1.0
BEGIN TRANSACTION;

INSERT INTO books (id, title, pubdate, path, has_cover, series_index, timestamp, last_modified, uuid) VALUES
	(1, 'Unicode & "Quotes" — 日本語', '2026-01-02', 'Ada Lovelace/Unicode & "Quotes" — 日本語 (1)', 1, 2.0, '2026-01-03 04:05', '2026-01-04 05:06', '00000000-0000-4000-8000-000000000001'),
	(2, 'Book with missing optional fields', '2025-02-03', 'Anonymous/Missing fields (2)', 0, 1.0, '2025-02-04 05:06', '2025-02-05 06:07', null);
INSERT INTO comments (id, book, text) VALUES
	(1, 1, 'A description containing spaces, ampersands &, quotes " and 日本語.');
INSERT INTO identifiers (id, book, type, val) VALUES
	(1, 1, 'isbn', '978-0-00-000001-1');
INSERT INTO data (id, book, format, uncompressed_size, name) VALUES
	(1, 1, 'EPUB', 1, 'Unicode & "Quotes" — 日本語'),
	(2, 1, 'PDF', 1, 'Unicode & "Quotes" — 日本語');

INSERT INTO authors (id, name, sort, link) VALUES
	(1, 'Ada & "Lovelace"', 'Lovelace, Ada', ''),
	(2, '作者 日本語', '作者 日本語', '');
INSERT INTO books_authors_link (id, book, author) VALUES
	(1, 1, 1),
	(2, 1, 2),
	(3, 2, 2);

INSERT INTO languages (id, lang_code) VALUES
	(1, 'en'),
	(2, 'ja');
INSERT INTO books_languages_link (id, book, lang_code) VALUES
	(1, 1, 1),
	(2, 1, 2),
	(3, 2, 2);

INSERT INTO publishers (id, name) VALUES
	(1, 'Publisher & Sons');
INSERT INTO books_publishers_link (id, book, publisher) VALUES
	(1, 1, 1);

INSERT INTO series (id, name) VALUES
	(1, 'Series "One" & 日本語');
INSERT INTO books_series_link (id, book, series) VALUES
	(1, 1, 1);

INSERT INTO tags (id, name) VALUES
	(1, 'Unicode & metadata'),
	(2, '日本語');
INSERT INTO books_tags_link (id, book, tag) VALUES
	(1, 1, 1),
	(2, 1, 2),
	(3, 2, 2);

COMMIT;
