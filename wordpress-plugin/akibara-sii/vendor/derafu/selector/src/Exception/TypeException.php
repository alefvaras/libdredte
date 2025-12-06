<?php

declare(strict_types=1);

/**
 * Derafu: Selector - Elegant Data Structure Navigation for PHP.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Selector\Exception;

use Throwable;

/**
 * Exception thrown when a type mismatch occurs.
 *
 * This exception is used when trying to perform operations on values
 * of incompatible types, like traversing a non-array value.
 */
class TypeException extends SelectorException
{
    /**
     * Creates a new TypeException.
     *
     * @param string $message The exception message.
     * @param int $code The exception code (optional).
     * @param Throwable|null $previous Previous exception if any.
     */
    public function __construct(
        string $message = '',
        int $code = 0,
        ?Throwable $previous = null
    ) {
        parent::__construct(
            "Type error: {$message}",
            $code,
            $previous
        );
    }
}
