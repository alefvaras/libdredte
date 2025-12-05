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
use Derafu\Selector\Selector;

/**
 * Implements simple dot notation selector type (e.g., "user.name").
 *
 * This class handles the most basic form of selectors, where each part
 * represents a direct key in the array structure. It supports nested
 * navigation through the dot notation.
 *
 * @example
 * ```php
 * // For data structure:
 * $data = [
 *     'user' => [
 *         'name' => 'John',
 *         'email' => 'john@example.com'
 *     ]
 * ];
 *
 * // The selector "user.name" would access "John"
 * ```
 */
final class SimpleSelector implements SelectorTypeInterface
{
    /**
     * @param string $key The current key to access in the data structure.
     * @param string $nextPath Remaining path after this key (may be empty).
     */
    public function __construct(
        private readonly string $key,
        private readonly string $nextPath = ''
    ) {
    }

    /**
     * Reads a value from the data structure using simple key access.
     *
     * Handles both direct key access and nested paths through recursion.
     *
     * @param array $data The data structure to read from.
     * @return mixed The value found at the selector's path or null if not found.
     */
    public function read(array $data): mixed
    {
        // Check if the current key exists.
        if (!array_key_exists($this->key, $data)) {
            return null;
        }

        $current = $data[$this->key];

        // If we have a next path and current value is an array, continue
        // recursively.
        if ($this->nextPath !== '' && is_array($current)) {
            $parser = Selector::getParser();
            $nextSelector = $parser->parse($this->nextPath);
            return $nextSelector->read($current);
        }

        // Otherwise return the current value.
        return $current;
    }

    /**
     * Writes a value to the data structure using simple key access.
     *
     * Creates intermediate arrays as needed for nested paths.
     *
     * @param array $data The data structure to modify.
     * @param mixed $value The value to write.
     * @throws SelectorException If trying to traverse through a non-array value.
     */
    public function write(array &$data, mixed $value): void
    {
        // If we have a next path, ensure we can traverse.
        if ($this->nextPath !== '') {
            // Initialize the current key as array if it doesn't exist.
            if (!array_key_exists($this->key, $data)) {
                $data[$this->key] = [];
            }

            // Ensure we can traverse through the current value.
            if (!is_array($data[$this->key])) {
                throw new SelectorException(
                    "Cannot traverse through non-array value at key '{$this->key}'"
                );
            }

            // Continue recursively.
            $parser = Selector::getParser();
            $nextSelector = $parser->parse($this->nextPath);
            $nextSelector->write($data[$this->key], $value);
            return;
        }

        // No next path, perform direct write.
        if (is_array($value) && isset($data[$this->key]) && is_array($data[$this->key])) {
            // If both current and new values are arrays, merge them.
            $data[$this->key] = array_merge($data[$this->key], $value);
        } else {
            // Otherwise just assign the value.
            $data[$this->key] = $value;
        }
    }

    /**
     * Clears (removes) a value from the data structure.
     *
     * For nested paths, also removes empty parent arrays.
     *
     * @param array $data The data structure to modify.
     */
    public function clear(array &$data): void
    {
        // If key doesn't exist, nothing to do.
        if (!array_key_exists($this->key, $data)) {
            return;
        }

        // If we have a next path and current value is an array.
        if ($this->nextPath !== '' && is_array($data[$this->key])) {
            $parser = Selector::getParser();
            $nextSelector = $parser->parse($this->nextPath);
            $nextSelector->clear($data[$this->key]);

            // Remove parent array if it became empty.
            if (empty($data[$this->key])) {
                unset($data[$this->key]);
            }
            return;
        }

        // No next path or current value is not an array, simply unset.
        unset($data[$this->key]);
    }
}
