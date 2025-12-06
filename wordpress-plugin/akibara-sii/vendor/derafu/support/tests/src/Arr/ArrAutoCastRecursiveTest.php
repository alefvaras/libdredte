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
use PHPUnit\Framework\TestCase;

#[CoversClass(Arr::class)]
class ArrAutoCastRecursiveTest extends TestCase
{
    public function testAutoCastRecursive()
    {
        $array = [
            'integerString' => '42',
            'floatString' => '42.42',
            'negativeIntegerString' => '-42',
            'negativeFloatString' => '-42.42',
            'emptyString' => '',
            'stringWithSpaces' => '   123   ',
            'nonNumericString' => 'hello',
            'arrayWithMixedValues' => [
                'nestedIntegerString' => '10',
                'nestedFloatString' => '10.10',
                'nestedEmptyString' => '',
                'nestedNonNumericString' => 'world',
            ],
            'nonStringValue' => true, // Should remain unchanged.
        ];

        $expected = [
            'integerString' => 42,
            'floatString' => 42.42,
            'negativeIntegerString' => -42,
            'negativeFloatString' => -42.42,
            'emptyString' => null, // Default empty value for test.
            'stringWithSpaces' => 123, // Trimmed and casted to int.
            'nonNumericString' => 'hello', // Remains unchanged.
            'arrayWithMixedValues' => [
                'nestedIntegerString' => 10,
                'nestedFloatString' => 10.10,
                'nestedEmptyString' => null, // Default empty value for test.
                'nestedNonNumericString' => 'world', // Remains unchanged.
            ],
            'nonStringValue' => true, // Remains unchanged.
        ];

        $result = Arr::cast($array, null);

        $this->assertSame($expected, $result, 'The array was not transformed as expected.');
    }

    public function testAutoCastRecursiveWithCustomEmptyValue()
    {
        $array = [
            'emptyString' => '',
            'nestedArray' => [
                'nestedEmptyString' => '',
            ],
        ];

        $expected = [
            'emptyString' => 'customValue',
            'nestedArray' => [
                'nestedEmptyString' => 'customValue',
            ],
        ];

        $result = Arr::cast($array, 'customValue');

        $this->assertSame($expected, $result, 'The custom empty value was not applied correctly.');
    }

    public function testAutoCastRecursiveNoCastsUnnecessaryValues()
    {
        $array = [
            'booleanTrue' => true,
            'booleanFalse' => false,
            'nullValue' => null,
            'integer' => 123,
            'float' => 123.45,
        ];

        $expected = $array; // Should remain unchanged.

        $result = Arr::cast($array);

        $this->assertSame($expected, $result, 'The non-string values were altered when they should not have been.');
    }
}
