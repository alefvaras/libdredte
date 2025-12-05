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

use Derafu\Selector\Exception\EvaluatorException;

/**
 * Evaluates conditions for the conditional selector.
 *
 * This class provides the logic for evaluating different types of conditions
 * such as equality, comparison, containment, etc. It normalizes values for
 * comparison and handles type conversion where appropriate.
 */
final class ConditionEvaluator
{
    /**
     * Evaluates a condition using the specified operator and values.
     *
     * @param mixed $valueA First value to compare.
     * @param string $operator The comparison operator.
     * @param mixed $valueB Second value to compare.
     * @return bool Result of the condition evaluation.
     * @throws EvaluatorException If the operator is not supported.
     */
    public function evaluate(mixed $valueA, string $operator, mixed $valueB): bool
    {
        // Convert boolean values to strings for consistent comparison.
        if (is_bool($valueA)) {
            $valueA = $valueA ? 'true' : 'false';
        }

        return match ($operator) {
            // Equality operators.
            '=', '==' => $this->evaluateEquality($valueA, $valueB),
            '!=', '<>' => !$this->evaluateEquality($valueA, $valueB),

            // Comparison operators.
            '>' => $this->evaluateComparison($valueA, $valueB, '>'),
            '>=' => $this->evaluateComparison($valueA, $valueB, '>='),
            '<' => $this->evaluateComparison($valueA, $valueB, '<'),
            '<=' => $this->evaluateComparison($valueA, $valueB, '<='),

            // Special operators.
            'contains' => $this->evaluateContains($valueA, $valueB),
            'length' => $this->evaluateLength($valueA, $valueB),
            'is' => $this->evaluateIs($valueA, $valueB),

            default => throw new EvaluatorException(
                "Operator not implemented: {$operator}"
            )
        };
    }

    /**
     * Evaluates equality between two values.
     *
     * @param mixed $valueA First value.
     * @param mixed $valueB Second value.
     * @return bool True if values are equal.
     */
    private function evaluateEquality(mixed $valueA, mixed $valueB): bool
    {
        $a = $this->normalizeValue($valueA);
        $b = $this->normalizeValue($valueB);

        return $a === $b;
    }

    /**
     * Normalizes values for comparison.
     *
     * Handles special cases like boolean values and strings with quotes.
     * Boolean values are converted to 'true'/'false' strings.
     * String values have their surrounding quotes removed.
     *
     * @param mixed $value The value to normalize.
     * @return string The normalized value as a string.
     */
    private function normalizeValue(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }
        return trim((string)$value, '"');
    }

    /**
     * Evaluates numeric comparison between two values.
     *
     * @param mixed $valueA First value.
     * @param mixed $valueB Second value.
     * @param string $operator Comparison operator.
     * @return bool Result of the comparison.
     */
    private function evaluateComparison(
        mixed $valueA,
        mixed $valueB,
        string $operator
    ): bool {
        $a = is_numeric($valueA) ? (float)$valueA : (string)$valueA;
        $b = is_numeric($valueB) ? (float)$valueB : (string)$valueB;

        return match ($operator) {
            '>' => $a > $b,
            '>=' => $a >= $b,
            '<' => $a < $b,
            '<=' => $a <= $b,
            default => false
        };
    }

    /**
     * Evaluates if one value contains another.
     *
     * Works with both arrays and strings.
     *
     * @param mixed $valueA Value to search in (array or string).
     * @param mixed $valueB Value to search for.
     * @return bool True if valueA contains valueB.
     */
    private function evaluateContains(mixed $valueA, mixed $valueB): bool
    {
        if (is_array($valueA)) {
            $valueB = is_numeric($valueB)
                ? (float) trim($valueB, '"')
                : (string) $valueB
            ;

            return in_array(
                $valueB,
                array_map(
                    fn ($v) => is_numeric($v) ? (float)$v : (string)$v,
                    $valueA
                )
            );
        }

        return str_contains((string)$valueA, (string)$valueB);
    }

    /**
     * Evaluates if a value has the specified length.
     *
     * Works with both arrays and strings.
     *
     * @param mixed $valueA Value to check length of.
     * @param mixed $valueB Expected length.
     * @return bool True if the length matches.
     */
    private function evaluateLength(mixed $valueA, mixed $valueB): bool
    {
        if (!is_array($valueA) && !is_string($valueA)) {
            return false;
        }

        $length = is_array($valueA) ? count($valueA) : strlen($valueA);

        $expected = trim($valueB, '"');

        return $length === (int)$expected;
    }

    /**
     * Evaluates special "is" conditions.
     *
     * Currently supports null checks.
     *
     * @param mixed $valueA Value to check.
     * @param mixed $valueB Condition to check against.
     * @return bool Result of the evaluation.
     */
    private function evaluateIs(mixed $valueA, mixed $valueB): bool
    {
        $valueB = trim($valueB, '"');
        if ($valueB === 'None' || $valueB === 'null') {
            return $valueA === null;
        }

        return $valueA !== 'null';
    }
}
