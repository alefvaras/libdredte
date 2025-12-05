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
 * Implements array index selector type (e.g., "items[0]").
 *
 * This class handles selectors that access array elements by their numeric
 * index. It supports both reading and writing to specific array positions,
 * with the ability to navigate further using nested paths.
 *
 * @example
 * ```php
 * // For data structure:
 * $data = [
 *     'items' => [
 *         ['id' => 1, 'name' => 'First'],
 *         ['id' => 2, 'name' => 'Second']
 *     ]
 * ];
 *
 * // The selector "items[0].name" would access "First"
 * ```
 */
final class ArraySelector implements SelectorTypeInterface
{
    /**
     * @param string $key The array key in the data structure.
     * @param int $index The numeric index to access within the array.
     * @param string $nextPath Remaining path after this array access.
     */
    public function __construct(
        private readonly string $key,
        private readonly int $index,
        private readonly string $nextPath = ''
    ) {
    }

    /**
     * Reads a value from the data structure using array index access.
     *
     * @param array $data The data structure to read from.
     * @return mixed The value at the specified array index or null if not found.
     */
    public function read(array $data): mixed
    {
        // Check if the key exists and is an array.
        if (!isset($data[$this->key]) || !is_array($data[$this->key])) {
            return null;
        }

        // Check if the index exists.
        if (!array_key_exists($this->index, $data[$this->key])) {
            return null;
        }

        $current = $data[$this->key][$this->index];

        // If we have a next path and current value is an array, continue
        // recursively.
        if ($this->nextPath !== '' && is_array($current)) {
            $parser = Selector::getParser();
            $nextSelector = $parser->parse($this->nextPath);
            return $nextSelector->read($current);
        }

        return $current;
    }

    /**
     * Writes a value to the data structure using array index access.
     *
     * Creates intermediate arrays as needed and handles array merging
     * when appropriate.
     *
     * @param array $data The data structure to modify.
     * @param mixed $value The value to write.
     * @throws SelectorException If trying to traverse through a non-array value.
     */
    public function write(array &$data, mixed $value): void
    {
        // Initialize the key as array if it doesn't exist.
        if (!isset($data[$this->key])) {
            $data[$this->key] = [];
        }

        // Ensure key points to an array.
        if (!is_array($data[$this->key])) {
            throw new SelectorException(
                "Cannot access index on non-array at key '{$this->key}'"
            );
        }

        // Initialize the index if needed.
        if (!array_key_exists($this->index, $data[$this->key])) {
            $data[$this->key][$this->index] = [];
        }

        // If we have a next path, ensure we can traverse and continue
        // recursively.
        if ($this->nextPath !== '') {
            if (!is_array($data[$this->key][$this->index])) {
                throw new SelectorException(
                    "Cannot traverse through non-array value at index {$this->index}"
                );
            }

            $parser = Selector::getParser();
            $nextSelector = $parser->parse($this->nextPath);
            $nextSelector->write($data[$this->key][$this->index], $value);
            return;
        }

        // No next path, perform direct write.
        if (is_array($value) && is_array($data[$this->key][$this->index])) {
            // If both current and new values are arrays, merge them.
            $data[$this->key][$this->index] = array_merge(
                $data[$this->key][$this->index],
                $value
            );
        } else {
            // Otherwise just assign the value.
            $data[$this->key][$this->index] = $value;
        }
    }

    /**
     * Clears (removes) a value from the data structure at the specified array
     * index.
     *
     * Also handles cleanup of empty parent arrays.
     *
     * @param array $data The data structure to modify.
     */
    public function clear(array &$data): void
    {
        // If key doesn't exist or is not an array, nothing to do.
        if (!isset($data[$this->key]) || !is_array($data[$this->key])) {
            return;
        }

        // If we have a next path and current index exists and is an array.
        if (
            $this->nextPath !== '' &&
            isset($data[$this->key][$this->index]) &&
            is_array($data[$this->key][$this->index])
        ) {
            $parser = Selector::getParser();
            $nextSelector = $parser->parse($this->nextPath);
            $nextSelector->clear($data[$this->key][$this->index]);

            // Remove the index if it became empty.
            if (empty($data[$this->key][$this->index])) {
                unset($data[$this->key][$this->index]);
            }

            // Remove the key if it became empty.
            if (empty($data[$this->key])) {
                unset($data[$this->key]);
            }
            return;
        }

        // No next path or current value is not an array, simply unset the index.
        unset($data[$this->key][$this->index]);

        // Remove the key if it became empty.
        if (empty($data[$this->key])) {
            unset($data[$this->key]);
        }
    }
}
