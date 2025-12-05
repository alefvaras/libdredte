<?php

declare(strict_types=1);

/**
 * Derafu: Selector - Elegant Data Structure Navigation for PHP.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Selector\Parser;

/**
 * Tokenizes selector expressions into their individual components.
 *
 * This class handles breaking down complex selector expressions into individual
 * parts while preserving their relationships and operations.
 * It handles nested parentheses, string literals, and operators at
 * different levels.
 */
final class SelectorTokenizer
{
    /**
     * Tokenize a selector expression into its individual parts.
     *
     * The returned array contains elements with:
     *
     *   - type: 'selector'|'if'|'string'|'operator'.
     *   - id/value: The actual content depending on type.
     *
     * @param string $expression The selector expression to tokenize.
     * @return array The array of tokenized parts.
     */
    public function tokenize(string $expression): array
    {
        $tokens = [];
        $startIndex = 0;
        $inSelector = false;
        $inString = false;
        $depth = 0;
        $length = strlen($expression);

        for ($index = 0; $index < $length; $index++) {
            $char = $expression[$index];

            // Handle parentheses.
            if ($char == '(' && !$inString) {
                $depth++;
                if (!$inSelector) {
                    $inSelector = true;
                    $startIndex = $index;
                }
            } elseif ($char == ')' && !$inString) {
                $depth--;
                if ($inSelector && $depth === 0) {
                    $inSelector = false;
                    $selectorId = substr(
                        $expression,
                        $startIndex + 1,
                        $index - $startIndex - 1
                    );
                    if (str_contains($selectorId, '?')) {
                        $tokens[] = ['type' => 'if', 'id' => $selectorId];
                    } else {
                        $tokens[] = ['type' => 'selector', 'id' => $selectorId];
                    }
                    $startIndex = $index + 1;
                }
            }
            // Handle string literals.
            elseif ($char === '"' && !$inSelector) {
                $inString = !$inString;
                if (!$inString) {
                    $tokens[] = [
                        'type' => 'string',
                        'value' => substr(
                            $expression,
                            $startIndex + 1,
                            $index - $startIndex - 1
                        ),
                    ];
                    $startIndex = $index + 1;
                }
            }
            // Handle OR operator (||) at top level.
            elseif (
                $char === '|'
                && $index < strlen($expression) - 1
                && $expression[$index + 1] === '|'
                && !$inString
                && !$inSelector
                && $depth === 0
            ) {
                $tokens[] = ['type' => 'operator', 'value' => 'or'];
                $startIndex = $index + 2;
                $index++;
            }
        }

        return $tokens;
    }
}
