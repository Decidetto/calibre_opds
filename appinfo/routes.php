<?php

declare(strict_types=1);

return [
	'routes' => [
		[
			// Root index
			'name' => 'opds#index',
			'url' => '/'
		],
		[
			// Authors by prefix or all authors
			'name' => 'opds#authors',
			'url' => '/authors/{prefix}',
			'defaults' => [ 'prefix' => '' ]
		],
		[
			// Prefixes for authors
			'name' => 'opds#author_prefixes',
			'url' => '/author-prefixes/{length}',
			'defaults' => [ 'length' => '1' ]
		],
		[
			// Publishers
			'name' => 'opds#publishers',
			'url' => '/publishers'
		],
		[
			// Languages
			'name' => 'opds#languages',
			'url' => '/languages'
		],
		[
			// Series
			'name' => 'opds#series',
			'url' => '/series'
		],
		[
			// Tags
			'name' => 'opds#tags',
			'url' => '/tags'
		],
		[
			// Books by criterion (author, publisher, etc) or search result or all books
			'name' => 'opds#books',
			'url' => '/books/{criterion}/{id}',
			'defaults' => [ 'criterion' => '', 'id' => '' ]
		],
		[
			// OpenSearch descriptor
			'name' => 'opds#search_xml',
			'url' => '/search.xml'
		],
		[
			// Book acquisition
			'name' => 'opds#book_data',
			'url' => '/data/{id}/{type}',
			'requirements' => [ 'id' => '[0-9]+', 'type' => '[A-Za-z0-9]{1,16}' ]
		],
		[
			// Book cover
			'name' => 'opds#book_cover',
			'url' => '/cover/{id}',
			'requirements' => [ 'id' => '[0-9]+' ]
		],
		// Settings controller
		[
			'name' => 'settings#settings',
			'url' => '/settings',
			'verb' => 'PUT',
		],
	]
];
