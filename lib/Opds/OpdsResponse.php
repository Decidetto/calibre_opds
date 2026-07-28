<?php

declare(strict_types=1);
// SPDX-FileCopyrightText: 2023 Alec Kojaev <alec@kojaev.name>
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OCA\Calibre2OPDS\Opds;

use ArrayIterator;
use DateTimeImmutable;
use DateTimeInterface;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Response;
use Traversable;
use XMLWriter;

/**
 * Implementation of OPDS feed response for Nextcloud.
 *
 * @template-extends Response<Http::STATUS_*, array<string, mixed>>
 */
final class OpdsResponse extends Response {
	/**
	 * Atom/OPDS feed MIME type.
	 */
	public const MIME_TYPE_ATOM = 'application/atom+xml;profile=opds-catalog';
	/**
	 * Identifier types for which URI doesn't need any prefix.
	 */
	public const LITERAL_IDENTIFIER_TYPES = [
		'uri', 'urn', 'epubbud'
	];

	/**
	 * "Last updated" timestamp for the feed.
	 */
	private DateTimeInterface $updated;
	/**
	 * List of feed links.
	 *
	 * @var array<OpdsLink>
	 */
	private array $links;
	/**
	 * List of feed entries.
	 *
	 * @var array<OpdsEntry>
	 */
	private array $entries;

	/**
	 * Construct an instance.
	 *
	 * @param OpdsApp $app common application attributes.
	 * @param string $id feed ID.
	 * @param string $title feed title.
	 * @param string|null $icon feed icon URL, or `null` if not defined.
	 */
	public function __construct(
		private OpdsApp $app,
		private string $id,
		private string $title,
		private ?string $icon = null,
	) {
		parent::__construct();
		$this->updated = new DateTimeImmutable();
		$this->links = [];
		$this->entries = [];
		$this->addHeader('Content-Type', self::MIME_TYPE_ATOM);
	}

	/**
	 * Get common application attributes.
	 *
	 * @return OpdsApp common application attributes.
	 */
	public function getOpdsApp(): OpdsApp {
		return $this->app;
	}

	/**
	 * Get feed ID.
	 *
	 * @return string feed ID.
	 */
	public function getId() : string {
		return $this->id;
	}

	/**
	 * Get feed title.
	 *
	 * @return string feed title.
	 */
	public function getTitle(): string {
		return $this->title;
	}

	/**
	 * Get feed icon URL.
	 *
	 * @return string|null feed icon URL, or `null` if not defined.
	 */
	public function getIconURL(): ?string {
		return $this->icon;
	}

	/**
	 * Get feed "last updated" timestamp.
	 *
	 * @return DateTimeInterface feed updated timestamp.
	 */
	public function getUpdated(): DateTimeInterface {
		return $this->updated;
	}

	/**
	 * Set feed "last updated" timestamp.
	 *
	 * @param DateTimeInterface $updated feed updated timestamp.
	 * @return self
	 */
	public function setUpdated(DateTimeInterface $updated): self {
		$this->updated = $updated;
		return $this;
	}

	/**
	 * Get feed links.
	 *
	 * @return Traversable<OpdsLink> feed links.
	 */
	public function getLinks(): Traversable {
		return new ArrayIterator($this->links);
	}

	/**
	 * Add a link for the feed.
	 *
	 * @param OpdsLink $link feed link.
	 * @return self
	 */
	public function addLink(OpdsLink $link): self {
		$this->links[] = $link;
		return $this;
	}

	/**
	 * Get feed entries.
	 *
	 * @return Traversable<OpdsEntry> feed entries.
	 */
	public function getEntries(): Traversable {
		return new ArrayIterator($this->entries);
	}

	/**
	 * Add an entry for the feed.
	 *
	 * @param OpdsEntry $entry feed entry.
	 * @return self
	 */
	public function addEntry(OpdsEntry $entry): self {
		$this->entries[] = $entry;
		return $this;
	}

	/**
	 * Write a link tag.
	 *
	 * @param XMLWriter $xml XML writer.
	 * @param OpdsLink $link link to write.
	 */
	private static function writeLink(XMLWriter $xml, OpdsLink $link): void {
		$xml->startElement('link');
		$xml->writeAttribute('rel', $link->getRel());
		$xml->writeAttribute('href', $link->getURL());
		$xml->writeAttribute('type', $link->getMimeType());
		$xml->endElement();
	}

	/**
	 * Write an author tag.
	 *
	 * @param XMLWriter $xml XML writer.
	 * @param OpdsAuthor $author author to write.
	 */
	private static function writeAuthor(XMLWriter $xml, OpdsAuthor $author): void {
		$xml->startElement('author');
		$xml->writeElement('name', $author->getName());
		if (!is_null($author->getURI())) {
			$xml->writeElement('uri', $author->getURI());
		}
		if (!is_null($author->getEMail())) {
			$xml->writeElement('email', $author->getEMail());
		}
		$xml->endElement();
	}

	/**
	 * Write a category tag.
	 *
	 * @param XMLWriter $xml XML writer.
	 * @param OpdsCategory $cat category to write.
	 */
	private static function writeCategory(XMLWriter $xml, OpdsCategory $cat): void {
		$xml->startElement('category');
		$xml->writeAttribute('term', $cat->getTerm());
		$schema = $cat->getSchema();
		if (!is_null($schema)) {
			$xml->writeAttribute('schema', $schema);
		}
		$label = $cat->getLabel();
		if (!is_null($label)) {
			$xml->writeAttribute('label', $label);
		}
		$xml->endElement();
	}

	#[\Override]
	public function render(): string {
		$xml = new XMLWriter();
		$xml->openMemory();
		$xml->setIndent(true);
		$xml->startDocument('1.0', 'utf-8');
		$xml->startElementNs(null, 'feed', 'http://www.w3.org/2005/Atom');
		$xml->writeAttributeNs('xmlns', 'dc', null, 'http://purl.org/dc/terms/');
		$xml->writeAttributeNs('xmlns', 'opds', null, 'http://opds-spec.org/2010/catalog');
		$xml->writeElement('id', 'opds:' . $this->id);
		foreach ($this->links as $link) {
			self::writeLink($xml, $link);
		}
		$xml->writeElement('title', $this->title);
		$xml->writeElement('updated', $this->updated->format('c'));
		self::writeAuthor($xml, new OpdsAuthor($this->app->getAppName(), $this->app->getAppWebsite()));
		$xml->startElement('generator');
		$xml->writeAttribute('uri', $this->app->getAppWebsite());
		$xml->writeAttribute('version', $this->app->getAppVersion());
		$xml->text($this->app->getAppName());
		$xml->endElement(); // </generator>
		if (!is_null($this->icon)) {
			$xml->writeElement('icon', $this->icon);
		}
		foreach ($this->entries as $entry) {
			$xml->startElement('entry');
			$xml->writeElement('id', 'opds:' . $entry->getId());
			$xml->writeElement('title', $entry->getTitle());
			$xml->writeElement('updated', ($entry->getUpdated() ?? $this->updated)->format('c'));
			foreach ($entry->getAuthors() as $author) {
				self::writeAuthor($xml, $author);
			}
			foreach ($entry->getCategories() as $cat) {
				self::writeCategory($xml, $cat);
			}
			foreach ($entry->getAttributes() as $attr) {
				$xml->writeElementNs($attr->getNs(), $attr->getTag(), null, $attr->getValueText());
			}
			foreach ($entry->getLinks() as $link) {
				self::writeLink($xml, $link);
			}
			$summary = $entry->getSummary();
			if (!is_null($summary)) {
				$xml->startElement('content');
				$xml->writeAttribute('type', 'html');
				$xml->text($summary);
				$xml->endElement(); // </content>
			}
			$xml->endElement(); // </entry>
		}
		$xml->endElement(); // </feed>
		$xml->endDocument();
		return $xml->outputMemory();
	}
}
