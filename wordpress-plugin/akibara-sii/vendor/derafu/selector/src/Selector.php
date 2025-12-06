<?php

declare(strict_types=1);

/**
 * Derafu: Selector - Elegant Data Structure Navigation for PHP.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Selector;

use Derafu\Selector\Evaluator\SelectorResolver;
use Derafu\Selector\Exception\SelectorException;
use Derafu\Selector\Operations\Cleaner;
use Derafu\Selector\Operations\Reader;
use Derafu\Selector\Operations\Writer;
use Derafu\Selector\Parser\SelectorParser;
use Derafu\Selector\Parser\SelectorTokenizer;

/**
 * Main Selector class that provides a facade for all selector operations.
 *
 * This class offers an elegant way to access and manipulate data structures
 * using a simple and powerful selector syntax. It supports:
 *
 *   - Simple dot notation (a.b.c).
 *   - Array indexing (items[0]).
 *   - Dependent selectors (users[id=123:name]).
 *   - Conditional selectors with ternary operations.
 *   - JSONPath and JMESPath integration.
 *
 * @example
 * ```php
 * $data = ['users' => [['id' => 1, 'name' => 'John']]];
 *
 * // Simple read.
 * $name = Selector::get($data, 'users[0].name');
 *
 * // Conditional write.
 * Selector::set($data, '((type) = "admin" ? (role) : (default_role))', 'user');
 * ```
 */
final class Selector
{
    private static SelectorParser $parser;

    private static Reader $reader;

    private static Writer $writer;

    private static Cleaner $cleaner;

    /**
     * Sets a value at the specified selector path.
     *
     * @param array $data The data structure to modify.
     * @param string $selector The selector path where to set the value.
     * @param mixed $value The value to set.
     * @throws SelectorException If the selector is invalid or operation fails.
     */
    public static function set(
        array &$data,
        string $selector,
        mixed $value
    ): void {
        self::getWriter()->write($data, $selector, $value);
    }

    /**
     * Gets a value from the specified selector path.
     *
     * @param array $data The data structure to read from.
     * @param string $selector The selector path to retrieve.
     * @param mixed $default Default value if selector doesn't exist.
     * @return mixed The value at the selector path or the default value.
     * @throws SelectorException If the selector is invalid.
     */
    public static function get(
        array $data,
        string $selector,
        mixed $default = null
    ): mixed {
        $value = self::getReader()->read($data, $selector);

        return $value ?? $default;
    }

    /**
     * Checks if a value exists at the specified selector path.
     *
     * @param array $data The data structure to check.
     * @param string $selector The selector path to verify.
     * @return bool True if the selector exists and has a non-null value.
     * @throws SelectorException If the selector is invalid.
     */
    public static function has(array $data, string $selector): bool
    {
        return self::get($data, $selector) !== null;
    }

    /**
     * Removes a value at the specified selector path.
     *
     * @param array $data The data structure to modify.
     * @param string $selector The selector path to clear.
     * @throws SelectorException If the selector is invalid or operation fails.
     */
    public static function clear(array &$data, string $selector): void
    {
        self::getCleaner()->clear($data, $selector);
    }

    public static function getParser(): SelectorParser
    {
        if (!isset(self::$parser)) {
            $tokenizer = new SelectorTokenizer();
            $resolver = new SelectorResolver();
            self::$parser = new SelectorParser($tokenizer, $resolver);
        }

        return self::$parser;
    }

    private static function getReader(): Reader
    {
        if (!isset(self::$reader)) {
            self::$reader = new Reader(self::getParser());
        }

        return self::$reader;
    }

    private static function getWriter(): Writer
    {
        if (!isset(self::$writer)) {
            self::$writer = new Writer(self::getParser());
        }

        return self::$writer;
    }

    private static function getCleaner(): Cleaner
    {
        if (!isset(self::$cleaner)) {
            self::$cleaner = new Cleaner(self::getParser());
        }

        return self::$cleaner;
    }
}
