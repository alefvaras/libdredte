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

/**
 * Interface for the handlers of the workers of the application.
 *
 * Orchestrates and groups complex logic that may include several jobs or
 * strategies.
 */
interface HandlerInterface extends ServiceInterface, JobsAwareInterface, StrategiesAwareInterface
{
    // This interface should be extended by defining specifically each handle()
    // method with its arguments types and return type.
    // public function handle();
}
