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
class ArrEnsureArrayAtPathTest extends TestCase
{
    public function testBasicConversion(): void
    {
        $data = [
            'Encabezado' => [
                'Detalle' => [
                    'Codigo' => '123',
                ],
            ],
        ];

        Arr::ensureArrayAtPath($data, 'Encabezado.Detalle');

        $this->assertIsArray($data['Encabezado']['Detalle']);
        $this->assertArrayHasKey(0, $data['Encabezado']['Detalle']);
        $this->assertSame(['Codigo' => '123'], $data['Encabezado']['Detalle'][0]);
    }

    public function testAlreadyArray(): void
    {
        $data = [
            'Encabezado' => [
                'Detalle' => [
                    ['Codigo' => '123'],
                ],
            ],
        ];

        Arr::ensureArrayAtPath($data, 'Encabezado.Detalle');

        $this->assertIsArray($data['Encabezado']['Detalle']);
        $this->assertArrayHasKey(0, $data['Encabezado']['Detalle']);
        $this->assertSame(['Codigo' => '123'], $data['Encabezado']['Detalle'][0]);
    }

    public function testNestedPath(): void
    {
        $data = [
            'Root' => [
                'Encabezado' => [
                    'Detalle' => [
                        'Codigo' => '456',
                    ],
                ],
            ],
        ];

        Arr::ensureArrayAtPath($data, 'Root.Encabezado.Detalle');

        $this->assertIsArray($data['Root']['Encabezado']['Detalle']);
        $this->assertArrayHasKey(0, $data['Root']['Encabezado']['Detalle']);
        $this->assertSame(['Codigo' => '456'], $data['Root']['Encabezado']['Detalle'][0]);
    }

    public function testEmptyPath(): void
    {
        $data = [];

        Arr::ensureArrayAtPath($data, 'Root.Encabezado.Detalle');

        $this->assertNull($data['Root']['Encabezado']['Detalle'] ?? null);
    }

    public function testEmptyArrayValue(): void
    {
        $data = [
            'Encabezado' => [
                'Detalle' => [],
            ],
        ];

        Arr::ensureArrayAtPath($data, 'Encabezado.Detalle');

        $this->assertIsArray($data['Encabezado']['Detalle']);
        $this->assertArrayNotHasKey(0, $data['Encabezado']['Detalle']);
    }
}
