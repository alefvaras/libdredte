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
 * Handles all read operations for selectors.
 *
 * This class implements the logic for reading values from data structures
 * using selectors. It handles both simple selectors and complex expressions
 * that may include concatenation, OR operations, and multiple parts.
 */
final class Reader
{
    /**
     * @param SelectorParser $parser The parser to use for selector expressions.
     */
    public function __construct(
        private readonly SelectorParser $parser
    ) {
    }

    /**
     * Reads a value from a data structure using a selector.
     *
     * @param array $data The data structure to read from.
     * @param string $selector The selector expression.
     * @return mixed The value found at the selector path.
     * @throws SelectorException If the selector syntax is invalid.
     */
    public function read(array $data, string $selector): mixed
    {
        if ($selector === '') {
            return null;
        }

        try {
            $selectorObj = $this->parser->parse($selector);
            return $selectorObj->read($data);
        } catch (SelectorException $e) {
            throw new SelectorException(
                "Failed to read using selector '{$selector}': " . $e->getMessage(),
                0,
                $e
            );
        }
    }
}
