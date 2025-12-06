<?php

declare(strict_types=1);

/**
 * Derafu: Support - Essential PHP Utilities.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\TestsSupport\Arr;

use Derafu\Support\Arr;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(Arr::class)]
class ArrDotNestedTest extends TestCase
{
    #[Test]
    #[DataProvider('provideDotData')]
    public function shouldFlattenArrayWithDotNotation(
        array $input,
        string $prefix,
        array $expected
    ): void {
        $result = Arr::dot($input, $prefix);
        $this->assertSame($expected, $result);
    }

    public static function provideDotData(): array
    {
        return [
            'simple nested array' => [
                ['a' => ['b' => 'c']],
                '',
                ['a.b' => 'c'],
            ],
            'with prefix' => [
                ['a' => ['b' => 'c']],
                'x',
                ['x.a.b' => 'c'],
            ],
            'flat array' => [
                ['a' => 1, 'b' => 2],
                '',
                ['a' => 1, 'b' => 2],
            ],
            'empty array' => [
                [],
                '',
                [],
            ],
        ];
    }

    #[Test]
    #[DataProvider('provideNestedData')]
    public function shouldConvertDotNotationToNestedArray(
        array $input,
        array $expected
    ): void {
        $result = Arr::nested($input);
        $this->assertSame($expected, $result);
    }

    public static function provideNestedData(): array
    {
        return [
            'simple dot notation' => [
                ['a.b' => 'c'],
                ['a' => ['b' => 'c']],
            ],
            'flat array' => [
                ['a' => 1, 'b' => 2],
                ['a' => 1, 'b' => 2],
            ],
            'empty array' => [
                [],
                [],
            ],
        ];
    }

    #[Test]
    #[DataProvider('provideCycleData')]
    public function shouldCompleteCycleDotToNestedAndBack(
        array $original
    ): void {
        $dot = Arr::dot($original);
        $nested = Arr::nested($dot);
        $this->assertSame($original, $nested);
    }

    public static function provideCycleData(): array
    {
        return [
            'simple nested structure' => [
                ['a' => ['b' => 'c']],
            ],
            'flat array' => [
                ['a' => 1, 'b' => 2],
            ],
            'empty array' => [
                [],
            ],
        ];
    }
}
