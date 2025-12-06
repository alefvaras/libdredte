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

use Derafu\Selector\Contract\SelectorTypeInterface;
use Derafu\Selector\Evaluator\SelectorResolver;
use Derafu\Selector\Exception\ParserException;
use Derafu\Selector\Types\ArraySelector;
use Derafu\Selector\Types\CompositeSelector;
use Derafu\Selector\Types\ConditionalSelector;
use Derafu\Selector\Types\DependentSelector;
use Derafu\Selector\Types\JmesPathSelector;
use Derafu\Selector\Types\JsonPathSelector;
use Derafu\Selector\Types\LiteralSelector;
use Derafu\Selector\Types\SimpleSelector;

/**
 * Parser for selector expressions that converts string selectors into
 * structured objects.
 *
 * This class is responsible for parsing different types of selectors:
 *
 *   - Simple dot notation (a.b.c)
 *   - Array indexing (items[0])
 *   - Dependent selectors (users[id=123:name])
 *   - Conditional selectors ((condition) ? (true_case) : (false_case))
 *   - Special selectors (JSONPath, JMESPath)
 */
final class SelectorParser
{
    public function __construct(
        private readonly SelectorTokenizer $tokenizer,
        private readonly SelectorResolver $resolver
    ) {
    }

    /**
     * Parse a selector string into a structured selector object.
     *
     * @param string $selector The selector string to parse.
     * @return SelectorTypeInterface The parsed selector object.
     * @throws ParserException If the selector syntax is invalid.
     */
    public function parse(string $selector): SelectorTypeInterface
    {
        // Validate and clean the selector.
        $selector = trim($selector);
        if ($selector === '' || str_ends_with($selector, '.')) {
            return new LiteralSelector(null);
        }

        // Normalize: if not starting with '(' or '"', wrap in parentheses.
        if (!str_starts_with($selector, '(') && !str_starts_with($selector, '"')) {
            $selector = "({$selector})";
        }

        // Get tokens and process.
        $tokens = $this->tokenizer->tokenize($selector);

        // CASE 1: Only one part and it is not an operator => return directly.
        if (count($tokens) === 1 && $tokens[0]['type'] !== 'operator') {
            $token = $tokens[0];
            if ($token['type'] === 'selector') {
                if (!str_contains($token['id'], '||')) {
                    return $this->resolveSelector($token['id']);
                }
            } elseif ($token['type'] === 'if') {
                return $this->resolveSelector($token['id']);
            } elseif ($token['type'] === 'string') {
                return new LiteralSelector($token['value']);
            }
        }

        // CASE 2: Composite selector.
        return new CompositeSelector($tokens);
    }

    /**
     * Resolves a selector to the instance of its type that allows it to execute.
     *
     * @param string $selector Selector to resolve.
     * @return SelectorTypeInterface
     */
    private function resolveSelector(string $selector): SelectorTypeInterface
    {
        $selector = trim($selector);
        if ($selector === '' || str_ends_with($selector, '.')) {
            return new LiteralSelector(null);
        }

        $resolved = $this->resolver->resolve($selector);
        if ($resolved === null) {
            return new LiteralSelector(null);
        }

        switch ($resolved->type) {
            case 'literal':
                return new LiteralSelector($resolved->value);
            case 'simple':
                return new SimpleSelector($resolved->part, $resolved->next_id);
            case 'array':
                return new ArraySelector(
                    $resolved->key,
                    $resolved->index,
                    $resolved->next_id
                );
            case 'dependent':
                return new DependentSelector(
                    $resolved->dictionaryKey,
                    $resolved->requiredKey,
                    $resolved->requiredValue,
                    $resolved->dependentKey,
                    $resolved->next_id
                );
            case 'if':
                return new ConditionalSelector(
                    $resolved->condition,
                    $resolved->operator,
                    $resolved->operatorValue,
                    $resolved->trueSelector,
                    $resolved->falseSelector,
                );
            case 'jsonpath':
                return new JsonPathSelector($selector);
            case 'jmespath':
                return new JmesPathSelector($selector);
            default:
                return new LiteralSelector(null);
        }
    }
}
