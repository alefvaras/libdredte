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

use Derafu\Backbone\Abstract\AbstractJob;
use Derafu\Backbone\Attribute\Job;
use Derafu\Backbone\Contract\JobInterface;

#[Job(name: 'example', worker: 'example', component: 'example', package: 'example')]
class ExampleJob extends AbstractJob implements JobInterface
{
    public function execute(): string
    {
        return 'Hello, world!';
    }
}
