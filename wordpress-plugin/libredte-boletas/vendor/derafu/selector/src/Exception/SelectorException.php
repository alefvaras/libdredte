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

use Derafu\Translation\Exception\Core\TranslatableRuntimeException;
use Throwable;

/**
 * Base exception class for all Selector-related exceptions.
 *
 * This is the parent class for all specific exceptions in the Selector library.
 * It allows catching all Selector-related exceptions with a single catch block
 * if needed.
 */
class SelectorException extends TranslatableRuntimeException
{
    /**
     * Creates a new SelectorException.
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
        parent::__construct($message, $code, $previous);
    }
}
