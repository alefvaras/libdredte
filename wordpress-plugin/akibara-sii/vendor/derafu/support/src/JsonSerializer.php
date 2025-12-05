<?php

declare(strict_types=1);

/**
 * Derafu: Support - Essential PHP Utilities.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Support;

use DateTime;
use DateTimeInterface;
use JsonException;
use JsonSerializable;
use Stringable;

/**
 * JSON serializer for converting PHP objects and arrays to JSON strings.
 *
 * This serializer handles the conversion of complex nested structures including
 * objects that implement JsonSerializable, have toArray() methods, or are
 * special types like DateTimeInterface and Stringable.
 */
final class JsonSerializer
{
    /**
     * Default flags used for JSON encoding.
     */
    private const DEFAULT_FLAGS = [
        JSON_PRETTY_PRINT,               // Format JSON with whitespace for readability.
        JSON_INVALID_UTF8_SUBSTITUTE,    // Replace invalid UTF-8 sequences with Unicode replacement character.
        JSON_UNESCAPED_LINE_TERMINATORS, // Don't escape line terminators.
        JSON_UNESCAPED_SLASHES,          // Don't escape forward slashes.
        JSON_UNESCAPED_UNICODE,          // Don't escape Unicode characters.
        JSON_THROW_ON_ERROR,             // Throw exception on encoding errors.
    ];

    /**
     * Serializes a PHP value to a JSON string.
     *
     * This method handles complex nested structures by recursively transforming
     * objects into arrays or scalar values that can be encoded to JSON.
     *
     * @param mixed $value The value to serialize to JSON.
     * @param int|null $flags JSON encoding flags (uses DEFAULT_FLAGS if null).
     * @param int $depth Maximum recursion depth.
     * @return string JSON encoded string.
     * @throws JsonException When encoding fails.
     */
    public static function serialize(
        mixed $value,
        ?int $flags = null,
        int $depth = 512
    ): string {
        // Use default flags if none provided.
        if ($flags === null) {
            $flags = array_reduce(
                self::DEFAULT_FLAGS,
                fn ($carry, $flag) => $carry | $flag,
                0
            );
        }

        // Transform objects to arrays or scalar values.
        self::transformObjects($value);

        // Encode to JSON.
        return json_encode($value, $flags, $depth);
    }

    /**
     * Recursively transforms objects in a value to make them JSON serializable.
     *
     * This method handles:
     *
     *   - Objects implementing JsonSerializable (calls jsonSerialize()).
     *   - Objects with toArray() method (calls toArray()).
     *   - DateTimeInterface objects (formats as ISO 8601 string).
     *   - Stringable objects (converts to string).
     *   - Arrays (processes each element recursively).
     *
     * @param mixed &$value The value to transform (passed by reference).
     * @return void
     */
    private static function transformObjects(mixed &$value): void
    {
        // Handle arrays by processing each element recursively.
        if (is_array($value)) {
            foreach ($value as &$item) {
                self::transformObjects($item);
            }
            // Break the reference to prevent accidental modifications.
            unset($item);
            return;
        }

        // If not an object, no transformation needed.
        if (!is_object($value)) {
            return;
        }

        // Handle different types of objects.

        // Get the serialized representation and transform it recursively.
        if ($value instanceof JsonSerializable) {

            $serialized = $value->jsonSerialize();
            $value = $serialized;
            self::transformObjects($value);
        }

        // Convert to array and transform recursively.
        elseif (method_exists($value, 'toArray')) {
            $array = $value->toArray();
            $value = $array;
            self::transformObjects($value);
        }

        // Format DateTime objects as ISO 8601 strings.
        elseif ($value instanceof DateTimeInterface) {
            $value = $value->format(DateTime::ATOM);
        }

        // Convert Stringable objects to strings.
        elseif ($value instanceof Stringable) {
            $value = $value->__toString();
        }

        // Objects that don't match any of the above conditions remain as is,
        // which may result in only public properties being encoded.
    }
}
