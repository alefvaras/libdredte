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

use Derafu\Backbone\Abstract\AbstractComponent;
use Derafu\Backbone\Attribute\Component;
use Derafu\Backbone\Contract\ComponentInterface;

#[Component(name: 'example', package: 'example')]
class ExampleComponent extends AbstractComponent implements ComponentInterface
{
    public function __construct(
        private readonly ExampleWorker $exampleWorker
    ) {
    }

    public function getWorkers(): array
    {
        return [
            'example' => $this->exampleWorker,
        ];
    }

    public function getExampleWorker(): ExampleWorker
    {
        return $this->exampleWorker;
    }
}
