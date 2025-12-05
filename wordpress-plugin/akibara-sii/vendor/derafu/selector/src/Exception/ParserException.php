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
 * Exception thrown when parsing a selector expression fails.
 *
 * This exception is used when the selector syntax is invalid or cannot
 * be properly parsed into a selector object.
 */
class ParserException extends SelectorException
{
    /**
     * Creates a new ParserException.
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
            "Parser error: {$message}",
            $code,
            $previous
        );
    }
}
