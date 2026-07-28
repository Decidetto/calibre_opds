<?php

declare(strict_types=1);
// SPDX-FileCopyrightText: 2023 Alec Kojaev <alec@kojaev.name>
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Stubs;

use Exception;
use OCA\Calibre2OPDS\Calibre\CalibreItem;
use OCA\Calibre2OPDS\Calibre\ICalibreDB;
use ReflectionClass;

trait CalibreStub {
	protected function createCalibreItem(string $cls, ICalibreDB $db, array $data, ?array $subData = null): CalibreItem {
		$instCls = new ReflectionClass($cls);
		if (!$instCls->isSubclassOf(CalibreItem::class)) {
			throw new Exception('class ' . $cls . ' is not a subclass of CalibreItem');
		}
		$instConstructor = $instCls->getConstructor();
		/** @var CalibreItem */
		$inst = $instCls->newInstanceWithoutConstructor();
		$instConstructor->invoke($inst, $db, $data);
		if (!is_null($subData)) {
			$prop = $instCls->getParentClass()->getProperty('data');
			$value = $prop->getValue($inst);
			$prop->setValue($inst, array_merge($value, $subData));
		}
		return $inst;
	}
}
