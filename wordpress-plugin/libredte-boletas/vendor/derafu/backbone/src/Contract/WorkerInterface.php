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
 * Interface for the workers of the application.
 *
 * Executes jobs and handlers.
 */
interface WorkerInterface extends ServiceInterface, JobsAwareInterface, HandlersAwareInterface
{
}
