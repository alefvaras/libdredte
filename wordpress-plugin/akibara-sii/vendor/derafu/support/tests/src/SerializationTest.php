<?php

declare(strict_types=1);

/**
 * Derafu: Support - Essential PHP Utilities.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\TestsSupport;

use Derafu\Support\Serialization;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use stdClass;

#[CoversClass(Serialization::class)]
class SerializationTest extends TestCase
{
    #[Test]
    #[DataProvider('provideSerializableData')]
    public function shouldDetermineIfValueIsSerializable(
        mixed $value,
        bool $expected
    ): void {
        $result = Serialization::isSerializable($value);
        $this->assertSame($expected, $result);
    }

    public static function provideSerializableData(): array
    {
        return [
            'array' => [
                ['test'],
                true,
            ],
            'object' => [
                new stdClass(),
                true,
            ],
            'null' => [
                null,
                false,
            ],
            'integer' => [
                42,
                false,
            ],
            'string' => [
                'test',
                false,
            ],
            'boolean' => [
                true,
                false,
            ],
            'float' => [
                3.14,
                false,
            ],
        ];
    }

    #[Test]
    #[DataProvider('provideSerializedData')]
    public function shouldDetermineIfDataIsSerialized(
        mixed $data,
        bool $expected
    ): void {
        $result = Serialization::isSerialized($data);
        $this->assertSame($expected, $result);
    }

    public static function provideSerializedData(): array
    {
        $obj = new stdClass();
        $obj->test = 'value';

        return [
            'null serialized' => [
                'N;',
                true,
            ],
            'string serialized' => [
                's:4:"test";',
                true,
            ],
            'integer serialized' => [
                'i:42;',
                true,
            ],
            'array serialized' => [
                serialize(['test']),
                true,
            ],
            'object serialized' => [
                serialize($obj),
                true,
            ],
            'boolean serialized' => [
                'b:1;',
                true,
            ],
            'float serialized' => [
                'd:3.14;',
                true,
            ],
            'not serialized string' => [
                'test',
                false,
            ],
            'not serialized array' => [
                ['test'],
                false,
            ],
            'not serialized number' => [
                42,
                false,
            ],
            'invalid serialized format' => [
                's:4:"test"',  // Missing semicolon.
                false,
            ],
            'malformed serialized data' => [
                'x:1:y;',  // Invalid type indicator.
                false,
            ],
            'empty string' => [
                '',
                false,
            ],
            'non-string type' => [
                null,
                false,
            ],
            'complex serialized array' => [
                serialize(['key' => ['nested' => 'value']]),
                true,
            ],
            'complex serialized object' => [
                serialize((object)['key' => ['nested' => 'value']]),
                true,
            ],
        ];
    }

    #[Test]
    public function shouldHandleEdgeCases(): void
    {
        // Test with resource type.
        $resource = fopen('php://memory', 'r');
        $this->assertTrue(Serialization::isSerializable($resource));
        fclose($resource);

        // Test with closure.
        $closure = fn () => true;
        $this->assertTrue(Serialization::isSerializable($closure));

        // Test with recursive array.
        $recursive = [];
        $recursive[] = &$recursive;
        $this->assertTrue(Serialization::isSerializable($recursive));
    }
}
