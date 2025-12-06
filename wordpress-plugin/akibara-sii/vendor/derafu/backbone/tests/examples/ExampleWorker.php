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

use Derafu\Backbone\Abstract\AbstractWorker;
use Derafu\Backbone\Attribute\Worker;
use Derafu\Backbone\Contract\WorkerInterface;

#[Worker(name: 'example', component: 'example', package: 'example')]
class ExampleWorker extends AbstractWorker implements WorkerInterface
{
    public function __construct(
        iterable $jobs
    ) {
        $this->setJobs($jobs);
    }

    public function getExampleJob(): ExampleJob
    {
        $job = $this->getJob('example');
        assert($job instanceof ExampleJob);
        return $job;
    }
}
