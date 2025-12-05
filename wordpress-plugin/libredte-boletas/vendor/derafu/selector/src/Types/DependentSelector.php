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
 * Implements dependent selector type (e.g., "users[id=123:name]").
 *
 * This class handles selectors that search for an element in an array based on
 * a key-value condition and then access a specific property of the found element.
 * It's particularly useful for accessing properties of objects in collections
 * when you know a unique identifying value.
 *
 * @example
 * ```php
 * // For data structure:
 * $data = [
 *     'users' => [
 *         ['id' => 123, 'name' => 'John', 'email' => 'john@example.com'],
 *         ['id' => 456, 'name' => 'Jane', 'email' => 'jane@example.com']
 *     ]
 * ];
 *
 * // The selector "users[id=123:name]" would access "John"
 * ```
 */
final class DependentSelector implements SelectorTypeInterface
{
    /**
     * @param string $dictionaryKey The key containing the array to search in.
     * @param string $requiredKey The key to match against in each array element.
     * @param string $requiredValue The value that requiredKey should match.
     * @param string $dependentKey The key to access in the matching element.
     * @param string $nextPath Remaining path after this dependent access.
     */
    public function __construct(
        private readonly string $dictionaryKey,
        private readonly string $requiredKey,
        private readonly string $requiredValue,
        private readonly string $dependentKey,
        private readonly string $nextPath = ''
    ) {
    }

    /**
     * Reads a value from the data structure using dependent conditions.
     *
     * Searches for an element in an array where requiredKey matches requiredValue,
     * then accesses the dependentKey from that element.
     *
     * @param array $data The data structure to read from.
     * @return mixed The value from the matching element or null if not found.
     */
    public function read(array $data): mixed
    {
        // Check if the dictionary key exists and is an array.
        if (!isset($data[$this->dictionaryKey]) || !is_array($data[$this->dictionaryKey])) {
            return null;
        }

        // Find the matching element.
        $element = $this->findMatchingElement($data[$this->dictionaryKey]);
        if ($element === null) {
            return null;
        }

        // If we found a match but dependentKey doesn't exist.
        if (!array_key_exists($this->dependentKey, $element)) {
            return null;
        }

        $current = $element[$this->dependentKey];

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
     * Writes a value to the data structure using dependent conditions.
     *
     * Finds or creates an element matching the required condition, then writes
     * the value to its dependent key.
     *
     * @param array $data The data structure to modify.
     * @param mixed $value The value to write.
     * @throws SelectorException If trying to traverse through a non-array value.
     */
    public function write(array &$data, mixed $value): void
    {
        // Initialize the dictionary key if it doesn't exist.
        if (!isset($data[$this->dictionaryKey])) {
            $data[$this->dictionaryKey] = [];
        }

        // Ensure dictionary key points to an array.
        if (!is_array($data[$this->dictionaryKey])) {
            throw new SelectorException(
                "Cannot access array elements on non-array at key '{$this->dictionaryKey}'"
            );
        }

        // Try to find existing element.
        $found = false;
        foreach ($data[$this->dictionaryKey] as &$element) {
            if ($this->elementMatches($element)) {
                $found = true;

                if ($this->nextPath !== '') {
                    if (!isset($element[$this->dependentKey])) {
                        $element[$this->dependentKey] = [];
                    }
                    if (!is_array($element[$this->dependentKey])) {
                        throw new SelectorException(
                            "Cannot traverse through non-array value at dependent key '{$this->dependentKey}'"
                        );
                    }

                    $parser = Selector::getParser();
                    $nextSelector = $parser->parse($this->nextPath);
                    $nextSelector->write($element[$this->dependentKey], $value);
                } else {
                    if (is_array($value) && isset($element[$this->dependentKey]) && is_array($element[$this->dependentKey])) {
                        $element[$this->dependentKey] = array_merge($element[$this->dependentKey], $value);
                    } else {
                        $element[$this->dependentKey] = $value;
                    }
                }
                break;
            }
        }

        // If no matching element was found, create a new one.
        if (!$found) {
            $newElement = [
                $this->requiredKey => $this->requiredValue,
                $this->dependentKey => $this->nextPath !== '' ? [] : $value,
            ];

            if ($this->nextPath !== '') {
                $parser = Selector::getParser();
                $nextSelector = $parser->parse($this->nextPath);
                $nextSelector->write($newElement[$this->dependentKey], $value);
            }

            $data[$this->dictionaryKey][] = $newElement;
        }
    }

    /**
     * Clears (removes) a value from the data structure at the dependent path.
     *
     * Finds the matching element and removes the dependent key or its nested path.
     *
     * @param array $data The data structure to modify.
     */
    public function clear(array &$data): void
    {
        // If dictionary key doesn't exist or is not an array, nothing to do.
        if (!isset($data[$this->dictionaryKey]) || !is_array($data[$this->dictionaryKey])) {
            return;
        }

        // Iterate through elements to find a match.
        foreach ($data[$this->dictionaryKey] as $index => &$element) {
            if ($this->elementMatches($element)) {
                if ($this->nextPath !== '') {
                    if (isset($element[$this->dependentKey]) && is_array($element[$this->dependentKey])) {
                        $parser = Selector::getParser();
                        $nextSelector = $parser->parse($this->nextPath);
                        $nextSelector->clear($element[$this->dependentKey]);

                        // If dependent key became empty, remove it.
                        if (empty($element[$this->dependentKey])) {
                            unset($element[$this->dependentKey]);
                        }
                    }
                } else {
                    unset($element[$this->dependentKey]);
                }

                // If the element became empty, remove it.
                if (empty($element)) {
                    unset($data[$this->dictionaryKey][$index]);
                }
                break;
            }
        }

        // If the dictionary array became empty, remove it.
        if (empty($data[$this->dictionaryKey])) {
            unset($data[$this->dictionaryKey]);
        }
    }

    /**
     * Finds the first element in an array that matches the required condition.
     *
     * @param array $array The array to search in.
     * @return array|null The matching element or null if not found.
     */
    private function findMatchingElement(array $array): ?array
    {
        foreach ($array as $element) {
            if ($this->elementMatches($element)) {
                return $element;
            }
        }
        return null;
    }

    /**
     * Checks if an element matches the required key-value condition.
     *
     * @param mixed $element The element to check.
     * @return bool True if the element matches the condition.
     */
    private function elementMatches(mixed $element): bool
    {
        return is_array($element) &&
               isset($element[$this->requiredKey]) &&
               (string)$element[$this->requiredKey] === (string)$this->requiredValue;
    }
}
