<?php

declare(strict_types=1);

/**
 * Derafu: Twig - UI Component and Extension Library.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Twig\Cache;

use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Cache\Adapter\PhpFilesAdapter;

class CacheItemPool extends PhpFilesAdapter implements CacheItemPoolInterface
{
    public function __construct(
        private string $namespace = 'derafu_twig',
        private int $defaultLifetime = 3600,
        private ?string $directory = null
    ) {
        $this->directory = $directory ?? __DIR__ . '/../../cache';

        parent::__construct($this->namespace, $this->defaultLifetime, $this->directory);
    }
}
