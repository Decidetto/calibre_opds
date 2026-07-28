<?php

declare(strict_types=1);
// SPDX-FileCopyrightText: 2023 Alec Kojaev <alec@kojaev.name>
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OCA\Calibre2OPDS\Controller;

use OCA\Calibre2OPDS\Service\ISettingsService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use OCP\PreConditionNotMetException;
use Psr\Log\LoggerInterface;
use UnexpectedValueException;

final class SettingsController extends Controller {
	public function __construct(
		IRequest $request,
		private ISettingsService $settings,
		private LoggerInterface $logger,
	) {
		parent::__construct($settings->getAppId(), $request);
	}

	#[NoAdminRequired]
	public function settings(string $libraryRoot): JSONResponse {
		try {
			$this->settings->setLibrary($libraryRoot);
		} catch (PreConditionNotMetException|UnexpectedValueException $e) {
			$this->logger->warning('Rejected Calibre library setting', [
				'exceptionClass' => $e::class,
			]);
			return new JSONResponse([
				'libraryRoot' => $this->settings->getLibrary(),
				'error' => 'Invalid library path',
			], Http::STATUS_BAD_REQUEST);
		}
		return new JSONResponse([
			'libraryRoot' => $this->settings->getLibrary()
		]);
	}
}
