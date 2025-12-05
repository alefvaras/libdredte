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
use Derafu\Selector\Exception\UnsupportedOperationException;
use Exception;
use JsonPath\JsonObject;

/**
 * Implements JSONPath selector type.
 *
 * This class provides access to data structures using JSONPath syntax.
 * It serves as a bridge to the JSONPath implementation while maintaining
 * the selector interface contract.
 *
 * @example
 * ```php
 * // For data structure:
 * $data = [
 *     'store' => [
 *         'books' => [
 *             ['title' => 'Book 1', 'price' => 10],
 *             ['title' => 'Book 2', 'price' => 20]
 *         ]
 *     ]
 * ];
 *
 * // The selector "$.store.books[?(@.price < 15)].title"
 * // would access ["Book 1"]
 * ```
 */
final class JsonPathSelector implements SelectorTypeInterface
{
    /**
     * @param string $expression The JSONPath expression to evaluate.
     */
    public function __construct(
        private readonly string $expression
    ) {
    }

    /**
     * Reads values from the data structure using JSONPath.
     *
     * @param array $data The data structure to read from.
     * @return mixed The value(s) matching the JSONPath expression.
     * @throws SelectorException If the JSONPath expression is invalid.
     */
    public function read(array $data): mixed
    {
        try {
            $jsonObject = new JsonObject($data);
            $result = $jsonObject->get($this->expression);

            // Handle empty results.
            if (is_array($result) && (empty($result) || $result === [null])) {
                return null;
            }

            // If result is a single-element array, return just that element.
            if (is_array($result) && count($result) === 1) {
                return $result[0];
            }

            return $result;
        } catch (Exception $e) {
            throw new SelectorException(
                "Invalid JSONPath expression: {$this->expression}",
                0,
                $e
            );
        }
    }

    /**
     * Writing with JSONPath is not supported.
     *
     * @param array $data The data structure to modify.
     * @param mixed $value The value to write.
     * @throws UnsupportedOperationException Always throws as operation is not supported.
     */
    public function write(array &$data, mixed $value): void
    {
        throw new UnsupportedOperationException(
            'Write operations are not supported with JSONPath selectors.'
        );
    }

    /**
     * Clearing with JSONPath is not supported.
     *
     * @param array $data The data structure to modify.
     * @throws UnsupportedOperationException Always throws as operation is not supported.
     */
    public function clear(array &$data): void
    {
        throw new UnsupportedOperationException(
            'Clear operations are not supported with JSONPath selectors.'
        );
    }
}
