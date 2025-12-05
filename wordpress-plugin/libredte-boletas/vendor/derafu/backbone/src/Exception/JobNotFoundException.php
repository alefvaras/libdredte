<?php

declare(strict_types=1);

/**
 * Derafu: Backbone - The Architectural Spine for PHP Libraries.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Backbone\Exception;

/**
 * Exception for jobs not found.
 */
class JobNotFoundException extends ServiceNotFoundException
{
    /**
     * Returns a new exception for a job not found.
     *
     * @param string $name The name of the job.
     * @return self
     */
    public static function forJob(string $name): self
    {
        $e = parent::forService($name, 'job');
        assert($e instanceof self);
        return $e;
    }
}
