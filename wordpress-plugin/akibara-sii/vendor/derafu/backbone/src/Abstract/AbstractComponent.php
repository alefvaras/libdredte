<?php

declare(strict_types=1);

/**
 * Derafu: Backbone - The Architectural Spine for PHP Libraries.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Backbone\Abstract;

use Derafu\Backbone\Contract\ComponentInterface;
use Derafu\Backbone\Contract\WorkerInterface;
use Derafu\Backbone\Exception\WorkerNotFoundException;
use Derafu\Config\Contract\ConfigurationInterface;
use Derafu\Config\Trait\ConfigurableTrait;

/**
 * Base class for the components of the application.
 */
abstract class AbstractComponent extends AbstractService implements ComponentInterface
{
    use ConfigurableTrait;

    /**
     * {@inheritDoc}
     */
    public function getWorker(string $name): WorkerInterface
    {
        // Gets all workers of the package.
        $workers = $this->getWorkers();

        // The worker was not found.
        if (!isset($workers[$name])) {
            throw WorkerNotFoundException::forWorker($name);
        }

        // Return the found worker.
        return $workers[$name];
    }

    /**
     * Gets the configuration of a worker of the component.
     *
     * @param string $name The name of the worker.
     * @return array|ConfigurationInterface
     */
    protected function getWorkerConfiguration(
        string $name
    ): array|ConfigurationInterface {
        $config = $this->getConfiguration()->get('workers.' . $name);

        return $config ?? [];
    }
}
