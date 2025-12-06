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

/**
 * Serialization utilities.
 *
 * Provides methods to work with PHP serialization.
 */
final class Serialization
{
    /**
     * Determines if a value can be serialized.
     *
     * @param mixed $value Value to check.
     * @return bool True if the value can be serialized.
     */
    public static function isSerializable(mixed $value): bool
    {
        if (is_array($value) || is_object($value)) {
            return true;
        }

        if (is_scalar($value) || is_null($value)) {
            return false;
        }

        return true;
    }

    /**
     * Determines if data is serialized.
     *
     * @param mixed $data Data to check.
     * @return bool True if the data is serialized.
     */
    public static function isSerialized(mixed $data): bool
    {
        if (!is_string($data)) {
            return false;
        }

        if ($data === 'N;') {
            return true;
        }

        if (preg_match('/^([adObis]):/', $data, $matches)) {
            switch ($matches[1]) {
                case 'a':
                case 'O':
                    return (bool)preg_match("/^{$matches[1]}:[0-9]+:/s", $data);
                case 's':
                    return (bool)preg_match("/^{$matches[1]}:[0-9]+:\".*\";$/s", $data);
                case 'b':
                case 'i':
                case 'd':
                    return (bool)preg_match("/^{$matches[1]}:[0-9.E-]+;$/", $data);
            }
        }

        return false;
    }
}
