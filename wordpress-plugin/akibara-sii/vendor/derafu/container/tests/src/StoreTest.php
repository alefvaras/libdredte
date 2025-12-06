<?php

declare(strict_types=1);

/**
 * Derafu: Container - Flexible Data Containers for PHP.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\TestsContainer;

use Derafu\Container\Store;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(Store::class)]
class StoreTest extends TestCase
{
    public static function provideTestCases(): array
    {
        $tests = require __DIR__ . '/../fixtures/store.php';

        $testCases = [];

        foreach ($tests as $name => $test) {
            $testCases[$name] = [
                $test['data'],
                $test['schema'],
                $test['expected'],
            ];
        }

        return $testCases;
    }

    public function testStoreSchemaGetterAndSetter(): void
    {
        $schema = [
            'required' => ['foo'],
            'properties' => [
                'foo' => [
                    'type' => 'string',
                ],
            ],
        ];

        $expected = [
            '$schema' => 'https://json-schema.org/draft/2020-12/schema',
            'type' => 'object',
            'required' => ['foo'],
            'properties' => [
                'foo' => [
                    'type' => 'string',
                ],
            ],
        ];

        $container = new Store();
        $container->setSchema($schema);
        $this->assertSame($expected, $container->getSchema());
    }

    #[DataProvider('provideTestCases')]
    public function testStoreCase(
        array $data,
        array $schema,
        array|string $expected
    ): void {
        if (is_string($expected)) {
            $this->expectException($expected);
        }

        $container = new Store($data, $schema);

        foreach ($expected as $selector => $expectedValue) {
            $this->assertSame($expectedValue, $container->get($selector));
        }
    }
}
