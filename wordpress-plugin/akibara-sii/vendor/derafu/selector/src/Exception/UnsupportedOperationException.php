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
 * Exception thrown when an operation is not supported.
 *
 * This exception is used when trying to perform operations that are
 * not supported by certain selector types, like writing to a literal
 * selector.
 */
class UnsupportedOperationException extends SelectorException
{
    /**
     * Creates a new UnsupportedOperationException.
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
            "Unsupported operation: {$message}",
            $code,
            $previous
        );
    }
}
