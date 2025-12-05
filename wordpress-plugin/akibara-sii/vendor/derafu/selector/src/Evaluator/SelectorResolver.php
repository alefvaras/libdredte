<?php

declare(strict_types=1);

/**
 * Derafu: Selector - Elegant Data Structure Navigation for PHP.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Selector\Evaluator;

use stdClass;

/**
 * Resolves the selector string into a structure representing it.
 */
class SelectorResolver
{
    /**
     * Resolves the selector string into a structure representing it.
     *
     * @param string $expression The selector to resolve.
     * @return stdClass|null Resolved selector or `null` if not valid.
     */
    public function resolve(string $expression): ?stdClass
    {
        return
            $this->literalString($expression)
            ?? $this->simpleSelector($expression)
            ?? $this->arraySelector($expression)
            ?? $this->dependentSelector($expression)
            ?? $this->conditionalSelector($expression)
            ?? $this->jsonPathSelector($expression)
            ?? $this->jmesPathSelector($expression)
            ?? null
        ;
    }

    /**
     * Resolves if the selector is a literal string (enclosed in double quotes).
     *
     * @param string $expression The selector to resolve.
     * @return stdClass|null Resolved selector or `null` if not valid.
     */
    private function literalString(string $expression): ?stdClass
    {
        if (str_starts_with($expression, '"') && str_ends_with($expression, '"')) {
            return (object) [
                'type' => 'literal',
                'value' => trim($expression, '"'),
            ];
        }

        return null;
    }

    /**
     * Resolves if the selector part is a simple word selector.
     *
     * @param string $expression The selector to resolve.
     * @return stdClass|null Resolved selector or `null` if not valid.
     */
    private function simpleSelector(string $expression): ?stdClass
    {
        $parts = explode('.', $expression, 2);
        $part = $parts[0];
        $nextId = $parts[1] ?? '';

        if (preg_match('/^(\w+)$/', $part, $matches)) {
            return (object) [
                'type' => 'simple',
                'part' => $matches[1],
                'next_id' => $nextId,
            ];
        }

        return null;
    }

    /**
     * Resolves if the selector part is an array accessor.
     *
     * @param string $expression The selector to resolve.
     * @return stdClass|null Resolved selector or `null` if not valid.
     */
    private function arraySelector(string $expression): ?stdClass
    {
        $parts = explode('.', $expression, 2);
        $part = $parts[0];
        $nextId = $parts[1] ?? '';

        if (preg_match('/^(\w+)\[(\d+)\]$/', $part, $matches)) {
            return (object) [
                'type' => 'array',
                'key' => $matches[1],
                'index' => (int) $matches[2],
                'next_id' => $nextId,
            ];
        }

        return null;
    }

    /**
     * Resolves if the selector part is a dependent selector.
     *
     * @param string $expression The selector to resolve.
     * @return stdClass|null Resolved selector or `null` if not valid.
     */
    private function dependentSelector(string $expression): ?stdClass
    {
        $parts = explode('.', $expression, 2);
        $part = $parts[0];
        $nextId = $parts[1] ?? '';

        if (
            preg_match(
                '/^(\w+)\[(\w+)=([\w\s\-]+):(\w+)\]$/',
                $part,
                $matches
            )
        ) {
            return (object) [
                'type' => 'dependent',
                'dictionaryKey' => $matches[1],
                'requiredKey' => $matches[2],
                'requiredValue' => $matches[3],
                'dependentKey' => $matches[4],
                'next_id' => $nextId,
            ];
        }

        return null;
    }

    /**
     * Resolves if the selector is a conditional (ternary) selector.
     *
     * @param string $expression The selector to resolve.
     * @return stdClass|null Resolved selector or `null` if not valid.
     */
    private function conditionalSelector(string $expression): ?stdClass
    {
        if (
            preg_match(
                '/\((.+?)\)\s*(\S+)\s*\"(.*?)\"\s*\?\s*\((.+?)\)\s*:\s*\((.+?)\)/',
                $expression,
                $matches
            )
        ) {
            return (object) [
                'type' => 'if',
                'condition' => $matches[1],
                'operator' => $matches[2],
                'operatorValue' => $matches[3],
                'trueSelector' => $matches[4],
                'falseSelector' => $matches[5],
            ];
        }

        return null;
    }

    /**
     * Resolves if the selector is a JSONPath selector.
     *
     * @param string $expression The selector to resolve.
     * @return stdClass|null Resolved selector or `null` if not valid.
     */
    private function jsonPathSelector(string $expression): ?stdClass
    {
        if (str_starts_with($expression, '$.')) {
            return (object) [
                'type' => 'jsonpath',
            ];
        }

        return null;
    }

    /**
     * Resolves if the selector is a JMESPath selector.
     *
     * @param string $expression The selector to resolve.
     * @return stdClass|null Resolved selector or `null` if not valid.
     */
    private function jmesPathSelector(string $expression): ?stdClass
    {
        if (str_starts_with($expression, 'jmespath:')) {
            return (object) [
                'type' => 'jmespath',
            ];
        }

        return null;
    }
}
