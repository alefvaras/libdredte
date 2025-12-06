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
use JmesPath\Env as JmesPath;

/**
 * Implements JMESPath selector type.
 *
 * This class provides access to data structures using JMESPath syntax.
 * It serves as a bridge to the JMESPath implementation while maintaining
 * the selector interface contract.
 *
 * @example
 * ```php
 * // For data structure:
 * $data = [
 *     'locations' => [
 *         ['name' => 'Seattle', 'state' => 'WA'],
 *         ['name' => 'New York', 'state' => 'NY']
 *     ]
 * ];
 *
 * // The selector "jmespath:locations[?state == 'WA'].name"
 * // would access ["Seattle"]
 * ```
 */
final class JmesPathSelector implements SelectorTypeInterface
{
    private readonly string $expression;

    /**
     * @param string $expression The JMESPath expression to evaluate.
     */
    public function __construct(string $expression)
    {
        // Remove the 'jmespath:' prefix.
        if (str_starts_with($expression, 'jmespath:')) {
            $expression = substr($expression, 9);
        }

        $this->expression = $expression;
    }

    /**
     * Reads values from the data structure using JMESPath.
     *
     * @param array $data The data structure to read from.
     * @return mixed The value(s) matching the JMESPath expression.
     * @throws SelectorException If the JMESPath expression is invalid.
     */
    public function read(array $data): mixed
    {
        try {
            $result = JmesPath::search($this->expression, $data);

            return match (true) {
                // Handle empty results.
                is_array($result) && empty($result) => null,
                // If result is a single-element array, return just that element.
                is_array($result) && count($result) === 1 => $result[0],
                // Otherwise return the result as is.
                default => $result
            };
        } catch (Exception $e) {
            throw new SelectorException(
                "Invalid JMESPath expression: {$this->expression}",
                0,
                $e
            );
        }
    }

    /**
     * Writing with JMESPath is not supported.
     *
     * @param array $data The data structure to modify.
     * @param mixed $value The value to write.
     * @throws UnsupportedOperationException Always throws as operation is not supported.
     */
    public function write(array &$data, mixed $value): void
    {
        throw new UnsupportedOperationException(
            'Write operations are not supported with JMESPath selectors.'
        );
    }

    /**
     * Clearing with JMESPath is not supported.
     *
     * @param array $data The data structure to modify.
     * @throws UnsupportedOperationException Always throws as operation is not supported.
     */
    public function clear(array &$data): void
    {
        throw new UnsupportedOperationException(
            'Clear operations are not supported with JMESPath selectors.'
        );
    }
}
