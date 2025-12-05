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
use Derafu\Selector\Evaluator\ConditionEvaluator;
use Derafu\Selector\Exception\SelectorException;
use Derafu\Selector\Parser\SelectorParser;
use Derafu\Selector\Selector;

/**
 * Implements conditional (ternary) selector type.
 *
 * This class handles ternary-style conditional selectors that choose between
 * two paths based on a condition. The syntax is similar to a ternary operator:
 * ((condition) operator "value" ? (true_selector) : (false_selector))
 *
 * @example
 * ```php
 * // For data structure:
 * $data = [
 *     'type' => 'admin',
 *     'basic_role' => 'user',
 *     'admin_role' => 'superuser'
 * ];
 *
 * // The selector ((type) = "admin" ? (admin_role) : (basic_role))
 * // would access "superuser"
 * ```
 */
final class ConditionalSelector implements SelectorTypeInterface
{
    private readonly ConditionEvaluator $evaluator;

    private readonly SelectorParser $parser;

    /**
     * @param string $condition The selector path for the condition value.
     * @param string $operator The comparison operator (=, !=, >, <, etc.).
     * @param string $operatorValue The value to compare against.
     * @param string $trueSelector The selector to use if condition is true.
     * @param string $falseSelector The selector to use if condition is false.
     */
    public function __construct(
        private readonly string $condition,
        private readonly string $operator,
        private readonly string $operatorValue,
        private readonly string $trueSelector,
        private readonly string $falseSelector
    ) {
        $this->evaluator = new ConditionEvaluator();
        $this->parser = Selector::getParser();
    }

    /**
     * Reads a value using conditional logic.
     *
     * First evaluates the condition, then delegates to either the true
     * or false selector based on the result.
     *
     * @param array $data The data structure to read from.
     * @return mixed The value from the chosen selector path.
     * @throws SelectorException If condition evaluation fails.
     */
    public function read(array $data): mixed
    {
        $useTrue = $this->evaluateCondition($data);

        // Parse and execute the appropriate selector
        $selectedPath = $useTrue ? $this->trueSelector : $this->falseSelector;
        $selector = $this->parser->parse($selectedPath);

        return $selector->read($data);
    }

    /**
     * Writes a value using conditional logic.
     *
     * Evaluates the condition and then delegates the write operation
     * to the appropriate selector.
     *
     * @param array $data The data structure to modify.
     * @param mixed $value The value to write.
     * @throws SelectorException If condition evaluation fails.
     */
    public function write(array &$data, mixed $value): void
    {
        $useTrue = $this->evaluateCondition($data);

        // Parse and execute the appropriate selector.
        $selectedPath = $useTrue ? $this->trueSelector : $this->falseSelector;
        $selector = $this->parser->parse($selectedPath);

        $selector->write($data, $value);
    }

    /**
     * Clears a value using conditional logic.
     *
     * Evaluates the condition and then delegates the clear operation
     * to the appropriate selector.
     *
     * @param array $data The data structure to modify.
     * @throws SelectorException If condition evaluation fails.
     */
    public function clear(array &$data): void
    {
        $useTrue = $this->evaluateCondition($data);

        // Parse and execute the appropriate selector.
        $selectedPath = $useTrue ? $this->trueSelector : $this->falseSelector;
        $selector = $this->parser->parse($selectedPath);

        $selector->clear($data);
    }

    /**
     * Evaluates the conditional expression.
     *
     * Gets the condition value using a temporary selector and evaluates it
     * against the operator and operator value.
     *
     * @param array $data The data structure to evaluate against.
     * @return bool The result of the condition evaluation.
     * @throws SelectorException If condition evaluation fails.
     */
    private function evaluateCondition(array $data): bool
    {
        // First get the condition value using a selector.
        $conditionSelector = $this->parser->parse($this->condition);
        $conditionValue = $conditionSelector->read($data);

        // Then evaluate it.
        return $this->evaluator->evaluate(
            $conditionValue,
            $this->operator,
            $this->operatorValue
        );
    }
}
