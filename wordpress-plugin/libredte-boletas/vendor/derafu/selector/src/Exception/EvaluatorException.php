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
 * Exception thrown when evaluating a condition fails.
 *
 * This exception is used in conditional selectors when the condition
 * evaluation fails, either due to invalid operators or incompatible values.
 */
class EvaluatorException extends SelectorException
{
    /**
     * Creates a new EvaluatorException.
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
            "Evaluator error: {$message}",
            $code,
            $previous
        );
    }
}
