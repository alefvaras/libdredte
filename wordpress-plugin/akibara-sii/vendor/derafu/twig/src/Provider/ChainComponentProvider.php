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

class ChainComponentProvider implements ComponentProviderInterface
{
    /** @var ComponentProviderInterface[] */
    private array $providers;

    public function __construct(ComponentProviderInterface ...$providers)
    {
        $this->providers = $providers;
    }

    public function getComponents(): array
    {
        $components = [];

        foreach ($this->providers as $provider) {
            $components = [...$components, ...$provider->getComponents()];
        }

        return array_unique($components);
    }
}
