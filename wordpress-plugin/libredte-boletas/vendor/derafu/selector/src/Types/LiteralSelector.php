<?php

declare(strict_types=1);

/**
 * Derafu: Selector - Elegant Data Structure Navigation for PHP.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Selector\Types;

use Derafu\Selector\Contract\SelectorTypeInterface;
use Derafu\Selector\Exception\SelectorException;

/**
 * Implements literal (constant) selector type.
 *
 * This selector type represents a literal value enclosed in double quotes in
 * the selector syntax. It's particularly useful in combination with other
 * selectors, especially in conditional expressions or when concatenating values.
 *
 * @example
 * ```php
 * // The selector '"Hello World"' would always return "Hello World"
 * // regardless of the data structure
 *
 * // More commonly used in combinations:
 * // ((type) = "admin" ? "Administrator" : "Regular User")
 * ```
 */
final class LiteralSelector implements SelectorTypeInterface
{
    /**
     * @param mixed $value The literal value this selector represents.
     */
    public function __construct(
        private readonly mixed $value
    ) {
    }

    /**
     * Returns the literal value, ignoring the input data structure.
     *
     * @param array $data The data structure (ignored for literals).
     * @return mixed The literal value.
     */
    public function read(array $data): mixed
    {
        return $this->value;
    }

    /**
     * Writing with literal selectors is not supported.
     *
     * @param array $data The data structure to modify.
     * @param mixed $value The value to write.
     * @throws SelectorException Always throws as operation is not supported.
     */
    public function write(array &$data, mixed $value): void
    {
        throw new SelectorException(
            'Write operations are not supported with literal selectors'
        );
    }

    /**
     * Clearing with literal selectors is not supported.
     *
     * @param array $data The data structure to modify.
     * @throws SelectorException Always throws as operation is not supported.
     */
    public function clear(array &$data): void
    {
        throw new SelectorException(
            'Clear operations are not supported with literal selectors'
        );
    }
}
