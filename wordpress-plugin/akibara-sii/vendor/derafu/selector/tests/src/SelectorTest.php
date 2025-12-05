<?php

declare(strict_types=1);

/**
 * Derafu: Selector - Elegant Data Structure Navigation for PHP.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\TestsSelector;

use Derafu\Selector\Evaluator\ConditionEvaluator;
use Derafu\Selector\Evaluator\SelectorResolver;
use Derafu\Selector\Operations\Reader;
use Derafu\Selector\Operations\Writer;
use Derafu\Selector\Parser\SelectorParser;
use Derafu\Selector\Parser\SelectorTokenizer;
use Derafu\Selector\Selector;
use Derafu\Selector\Types\ArraySelector;
use Derafu\Selector\Types\CompositeSelector;
use Derafu\Selector\Types\ConditionalSelector;
use Derafu\Selector\Types\DependentSelector;
use Derafu\Selector\Types\JmesPathSelector;
use Derafu\Selector\Types\JsonPathSelector;
use Derafu\Selector\Types\LiteralSelector;
use Derafu\Selector\Types\SimpleSelector;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(Selector::class)]
#[CoversClass(Reader::class)]
#[CoversClass(SelectorParser::class)]
#[CoversClass(SelectorTokenizer::class)]
#[CoversClass(SelectorResolver::class)]
#[CoversClass(ConditionEvaluator::class)]
#[CoversClass(ArraySelector::class)]
#[CoversClass(CompositeSelector::class)]
#[CoversClass(ConditionalSelector::class)]
#[CoversClass(DependentSelector::class)]
#[CoversClass(JsonPathSelector::class)]
#[CoversClass(JmesPathSelector::class)]
#[CoversClass(LiteralSelector::class)]
#[CoversClass(SimpleSelector::class)]
#[CoversClass(Writer::class)]
class SelectorTest extends TestCase
{
    public static function provideGetSelector(): array
    {
        $test = require __DIR__ . '/../fixtures/selector_get.php';

        $getSelectors = [];

        foreach ($test['cases'] as $selector => $expected) {
            $getSelectors[$selector] = [
                $test['data'],
                $selector,
                $expected,
            ];
        }

        return $getSelectors;
    }

    public static function provideSetSelector(): array
    {
        $tests = require __DIR__ . '/../fixtures/selector_set.php';

        $setSelectors = [];

        foreach ($tests as $test) {
            $setSelectors[$test['name']] = [
                $test['data'],
                $test['cases'],
            ];
        }

        return $setSelectors;
    }

    #[DataProvider('provideGetSelector')]
    public function testSelectorGet($data, $selector, $expected): void
    {
        $result = Selector::get($data, $selector);
        $this->assertSame($expected, $result);
    }

    #[DataProvider('provideSetSelector')]
    public function testSelectorSet($data, $cases): void
    {
        foreach ($cases as $case) {
            Selector::set($data, $case['selector'], $case['value']);
            $this->assertSame($case['expected'], $data);
        }
    }
}
