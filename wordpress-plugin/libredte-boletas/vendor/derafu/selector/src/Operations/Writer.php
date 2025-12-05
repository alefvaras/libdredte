<?php

declare(strict_types=1);

/**
 * Derafu: Selector - Elegant Data Structure Navigation for PHP.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Selector\Operations;

use Derafu\Selector\Exception\SelectorException;
use Derafu\Selector\Parser\SelectorParser;

/**
 * Handles all write operations for selectors.
 *
 * This class implements the logic for writing values to data structures
 * using selectors. It ensures proper initialization of nested structures
 * and handles array merging when appropriate.
 */
final class Writer
{
    /**
     * @param SelectorParser $parser The parser to use for selector expressions.
     */
    public function __construct(
        private readonly SelectorParser $parser
    ) {
    }

    /**
     * Writes a value to a data structure using a selector.
     *
     * @param array $data The data structure to modify.
     * @param string $selector The selector expression.
     * @param mixed $value The value to write.
     * @throws SelectorException If the selector syntax is invalid or write fails.
     */
    public function write(array &$data, string $selector, mixed $value): void
    {
        if ($selector === '') {
            return;
        }

        try {
            $selectorObj = $this->parser->parse($selector);
            $selectorObj->write($data, $value);
        } catch (SelectorException $e) {
            throw new SelectorException(
                "Failed to write using selector '{$selector}': " . $e->getMessage(),
                0,
                $e
            );
        }
    }
}
