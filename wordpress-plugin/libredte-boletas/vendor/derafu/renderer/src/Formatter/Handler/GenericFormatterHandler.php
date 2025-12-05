<?php

declare(strict_types=1);

/**
 * Derafu: Renderer - Unified Template Rendering Made Simple For PHP.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Renderer\Formatter\Handler;

use Closure;
use Throwable;

/**
 * Generic handler for formatting data values.
 *
 * This handler provides a flexible way to format values based on different
 * handler types:
 *
 *   - String handlers use sprintf format.
 *   - Array handlers use key-value mapping.
 *   - Closure handlers allow custom formatting logic.
 *   - Object handlers with 'find' method are treated as repositories.
 *
 * If no specific handling is possible, the value is cast to string using best
 * effort through the cast() method.
 *
 * @example Using string handler (sprintf):
 * ```php
 * $handler->handle(42, 'Value is: %d'); // "Value is: 42"
 * ```
 *
 * @example Using array handler (mapping):
 * ```php
 * $handler->handle('key', ['key' => 'value']); // "value"
 * ```
 *
 * @example Using closure handler:
 * ```php
 * $handler->handle(42, fn($v) => $v * 2); // "84"
 * ```
 *
 * @example Using object handler with find():
 * ```php
 * $repository = new UserRepository();
 * $handler->handle(1, $repository); // User::toString()
 * ```
 */
class GenericFormatterHandler
{
    /**
     * Handles the formatting of a value according to the provided handler.
     *
     * @param mixed $value The value to format.
     * @param string|array|object|null $handler Handler to use for formatting:
     *   - string: Used as sprintf format.
     *   - array: Used as value mapping.
     *   - Closure: Called with ($value, $format).
     *   - object: Must have find() method.
     *   - null: Direct casting attempted.
     * @param string|null $format The format identifier (used in some handlers).
     * @return string The formatted value.
     */
    public function handle(
        mixed $value,
        string|array|object|null $handler = null,
        ?string $format = null
    ): string {
        // If handler is a string, use it as a sprintf mask.
        if (is_string($handler)) {
            return sprintf($handler, $value);
        }

        // If handler is an array, use it as a value mapping.
        // If value not found in map, return original value as string.
        elseif (is_array($handler)) {
            return $this->cast($handler[$value] ?? $value);
        }

        // If Handler is an object, maybe is a Closure or a "repository".
        elseif (is_object($handler)) {
            // If handler is a Closure, call it directly.
            if ($handler instanceof Closure) {
                return $this->cast($handler($value, $format));
            }
            // If handler is an object with find(), treat as repository.
            elseif (method_exists($handler, 'find')) {
                $result = $handler->find($value);
                return $this->cast($result);
            }
        }

        // If no handler matched or handler is null, try direct casting
        return $this->cast($value);
    }

    /**
     * Attempts to cast a value to string using the most appropriate method.
     *
     * The casting priority is:
     *
     *   1. Direct string cast for scalar values and `null`.
     *   2. __toString() method if available.
     *   3. JSON encoding for arrays and objects.
     *   4. Error message if all else fails (not as exception, as a string).
     *
     * @param mixed $value The value to cast to string.
     * @return string The resulting string representation.
     */
    private function cast(mixed $value): string
    {
        // Direct cast for scalar values and null.
        if (is_scalar($value) || $value === null) {
            return (string) $value;
        }

        // Use __toString() if available.
        if (is_object($value) && method_exists($value, '__toString')) {
            return (string) $value;
        }

        // Try JSON encoding for arrays and objects.
        try {
            return json_encode($value, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
        } catch (Throwable $e) {
            return sprintf(
                'Serialization for data type %s failed: %s',
                get_debug_type($value),
                $e->getMessage()
            );
        }
    }
}
