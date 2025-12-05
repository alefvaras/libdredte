<?php

declare(strict_types=1);

/**
 * Derafu: Backbone - The Architectural Spine for PHP Libraries.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\ExamplesBackbone;

use Derafu\Backbone\Abstract\AbstractPackage;
use Derafu\Backbone\Attribute\Package;
use Derafu\Backbone\Contract\PackageInterface;

#[Package(name: 'example')]
class ExamplePackage extends AbstractPackage implements PackageInterface
{
    public function __construct(
        private readonly ExampleComponent $exampleComponent
    ) {
    }

    public function getComponents(): array
    {
        return [
            'example' => $this->exampleComponent,
        ];
    }

    public function getExampleComponent(): ExampleComponent
    {
        return $this->exampleComponent;
    }
}
