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
 * Debugging utilities.
 *
 * Provides methods for debugging variables and collecting debug information.
 */
final class Debug
{
    /**
     * Shows relevant debugging information for a variable.
     *
     * @param mixed $var Variable to inspect.
     * @param string|null $label Variable label (usually its name).
     * @return array Debug information collected.
     */
    public static function inspect(mixed $var, ?string $label = null): array
    {
        $backtrace = debug_backtrace();
        $debugCall = $backtrace[0];
        $debugCaller = $backtrace[1];

        $data = [
            'label' => $label ?? 'debug($var)',
            'type' => gettype($var),
            'length' => is_countable($var)
                ? count($var)
                : (is_string($var) ? strlen($var) : null),
            'file' => $debugCall['file'] ?? null,
            'line' => $debugCall['line'] ?? null,
            'caller' => isset($debugCaller['class'])
                ? "{$debugCaller['class']}::{$debugCaller['function']}()"
                : "{$debugCaller['function']}()",
            'timestamp' => microtime(true),
            'memory_usage' => memory_get_usage(),
            'value' => null,
        ];

        if (is_object($var)) {
            $data['type'] = get_class($var);
            $data['value'] = print_r($var, true);
        } elseif (is_null($var) || is_bool($var)) {
            $data['value'] = json_encode($var, JSON_PRETTY_PRINT);
        } else {
            $data['value'] = print_r($var, true);
        }

        return $data;
    }

    /**
     * Prints debug information in a formatted way.
     *
     * @param mixed $var Variable to debug.
     * @param string|null $label Variable label.
     * @return void
     */
    public static function print(mixed $var, ?string $label = null): void
    {
        echo '<pre>';
        print_r(self::inspect($var, $label));
        echo '</pre>';
    }
}
