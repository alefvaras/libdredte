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
 * Exception for components not found.
 */
class ComponentNotFoundException extends ServiceNotFoundException
{
    /**
     * Returns a new exception for a component not found.
     *
     * @param string $name The name of the component.
     * @return self
     */
    public static function forComponent(string $name): self
    {
        $e = parent::forService($name, 'component');
        assert($e instanceof self);
        return $e;
    }
}
