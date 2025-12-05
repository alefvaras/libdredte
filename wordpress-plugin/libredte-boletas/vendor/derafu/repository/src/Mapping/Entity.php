<?php

declare(strict_types=1);

/**
 * Derafu: Repository - Lightweight File Data Source Management for PHP.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Repository\Mapping;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class Entity
{
    public function __construct(
        public readonly string|null $repositoryClass = null,
        public readonly bool $readOnly = false,
    ) {
    }
}
