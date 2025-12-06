<?php

declare(strict_types=1);

/**
 * Derafu: Backbone - The Architectural Spine for PHP Libraries.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Backbone\Contract;

use Derafu\Backbone\Exception\WorkerNotFoundException;

/**
 * Interface for the component class of the application.
 */
interface ComponentInterface extends ServiceInterface
{
    /**
     * Returns a worker of the component.
     *
     * A worker is a service that implements WorkerInterface.
     *
     * @param string $name The name of the worker.
     * @return WorkerInterface The worker.
     * @throws WorkerNotFoundException If the worker is not registered.
     */
    public function getWorker(string $name): WorkerInterface;

    /**
     * Returns the list of workers of the component.
     *
     * A worker is a service that implements WorkerInterface.
     *
     * @return array<string, WorkerInterface> The list of workers.
     */
    public function getWorkers(): array;
}
