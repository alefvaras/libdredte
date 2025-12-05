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
 * Handles all clear (delete) operations for selectors.
 *
 * This class implements the logic for removing values from data structures
 * using selectors. It ensures proper cleanup of empty structures after
 * deletion.
 */
final class Cleaner
{
    /**
     * @param SelectorParser $parser The parser to use for selector expressions.
     */
    public function __construct(
        private readonly SelectorParser $parser
    ) {
    }

    /**
     * Clears (removes) a value from a data structure using a selector.
     *
     * @param array $data The data structure to modify.
     * @param string $selector The selector expression.
     * @throws SelectorException If the selector syntax is invalid or clear fails.
     */
    public function clear(array &$data, string $selector): void
    {
        if ($selector === '') {
            return;
        }

        try {
            $selectorObj = $this->parser->parse($selector);
            $selectorObj->clear($data);
        } catch (SelectorException $e) {
            throw new SelectorException(
                "Failed to clear using selector '{$selector}': " . $e->getMessage(),
                0,
                $e
            );
        }
    }
}
