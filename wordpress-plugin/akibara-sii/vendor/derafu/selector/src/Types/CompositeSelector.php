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
use Derafu\Selector\Exception\UnsupportedOperationException;
use Derafu\Selector\Parser\SelectorParser;
use Derafu\Selector\Selector;

/**
 * Implements composite selector type that handles multiple selector parts.
 *
 * This selector type manages expressions that combine multiple selectors
 * with operations like OR and concatenation.
 */
final class CompositeSelector implements SelectorTypeInterface
{
    private readonly SelectorParser $parser;

    /**
     * @param array $tokens Array of tokenized selector parts.
     */
    public function __construct(
        private array $tokens
    ) {
        $this->parser = Selector::getParser();
    }

    /**
     * Reads a value by evaluating all parts and combining them according to
     * operations.
     *
     * @param array $data The data structure to read from.
     * @return mixed The final combined value.
     */
    public function read(array $data): mixed
    {
        $finalValue = null;

        foreach ($this->tokens as $token) {
            if ($token['type'] === 'string') {
                $finalValue = self::concatenateValues(
                    $finalValue,
                    $token['value']
                );
            } elseif (in_array($token['type'], ['selector', 'if'])) {
                // Possible OR operators are searched for and processed.
                $subSelectors = explode('||', $token['id']);
                $orValueFound = false;
                foreach ($subSelectors as $subSelector) {
                    $value = $this->parser->parse($subSelector)->read($data);
                    if ($value !== null && $value !== '') {
                        $finalValue = self::concatenateValues(
                            $finalValue,
                            $value
                        );
                        $orValueFound = true;
                        break;
                    }
                }
                // If no valid value was found, the last value found is used.
                if (!$orValueFound) {
                    if (str_contains($token['id'], '||')) {
                        $value = null;
                    }
                    $finalValue = self::concatenateValues($finalValue, $value);
                }
            } elseif ($token['type'] === 'operator' && $token['value'] === 'or') {
                if ($finalValue !== null) {
                    break;
                }
            }
        }

        return $finalValue;
    }

    /**
     * Writing is not supported for composite selectors.
     *
     * @param array $data The data structure to modify.
     * @param mixed $value The value to write.
     * @throws UnsupportedOperationException Always throws as operation is not supported.
     */
    public function write(array &$data, mixed $value): void
    {
        throw new UnsupportedOperationException(
            'Write not supported for composite selectors.'
        );
    }

    /**
     * Clearing is not supported for composite selectors.
     *
     * @param array $data The data structure to modify.
     * @throws UnsupportedOperationException Always throws as operation is not supported.
     */
    public function clear(array &$data): void
    {
        throw new UnsupportedOperationException(
            'Clear not supported for composite selectors.'
        );
    }

    /**
     * Concatenates two values, handling special cases and type conversions.
     *
     * @param mixed $currentValue The current accumulated value.
     * @param mixed $newValue The new value to concatenate.
     * @return mixed The concatenated result.
     */
    private function concatenateValues(mixed $currentValue, mixed $newValue): mixed
    {
        if (is_array($newValue)) {
            $newValue = '[' . implode(', ', $newValue) . ']';
        }

        if ($currentValue === null) {
            return $newValue;
        }

        if (is_string($currentValue)) {
            if ($newValue === null) {
                $newValue = 'null';
            } elseif (is_bool($newValue)) {
                $newValue = $newValue ? 'true' : 'false';
            }

            return $currentValue . (string) $newValue;
        }

        if (is_bool($currentValue)) {
            $currentValue = $currentValue ? 'true' : 'false';
        }

        if (is_bool($newValue)) {
            $newValue = $newValue ? 'true' : 'false';
        }

        return (string) $currentValue . (string) $newValue;
    }
}
