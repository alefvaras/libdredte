<?php

declare(strict_types=1);

/**
 * Derafu: Twig - UI Component and Extension Library.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Twig\Provider;

use Derafu\Twig\Contract\ComponentProviderInterface;

class ArrayComponentProvider implements ComponentProviderInterface
{
    public function __construct(private array $components)
    {
    }

    public function getComponents(): array
    {
        return $this->components;
    }
}
