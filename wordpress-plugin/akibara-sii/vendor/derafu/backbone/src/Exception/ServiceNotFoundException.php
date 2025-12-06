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

use Derafu\Translation\Exception\Core\TranslatableLogicException;

/**
 * Exception for services not found.
 */
class ServiceNotFoundException extends TranslatableLogicException
{
    /**
     * Returns a new exception for a service not found.
     *
     * @param string $name The name of the service.
     * @param string $type The type of the service (package, component, etc).
     * @return self
     */
    public static function forService(string $name, string $type = 'service'): self
    {
        return new self([
            'The {type} {name} does not exist in the application.',
            [
                'type' => $type,
                'name' => $name,
            ],
        ]);
    }
}
