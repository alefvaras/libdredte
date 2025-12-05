<?php

declare(strict_types=1);

/**
 * Derafu: Repository - Lightweight File Data Source Management for PHP.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\TestsRepository;

use Derafu\Repository\Repository;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(Repository::class)]
class RepositoryTest extends TestCase
{
    public static function provideTestCases(): array
    {
        $tests = require dirname(__DIR__) . '/fixtures/repository.php';

        $testCases = [];

        foreach ($tests as $name => $test) {
            foreach ($test['cases'] as $caseName => $case) {
                $testCases[$name . ':' . $caseName] = [
                    $test['data'],
                    $case,
                ];
            }
        }

        return $testCases;
    }

    #[DataProvider('provideTestCases')]
    public function testRepositoryCase(array $data, array $case): void
    {
        $repository = new Repository($data, idAttribute: 'id');

        $method = $case['method'];
        $args = $case['args'];
        $expected = $case['expected'] ?? null;
        $expectedException = $case['expectedException'] ?? null;

        if ($expectedException !== null) {
            $this->expectException($expectedException);
        }

        $result = match($method) {
            'find' => $repository->find(...$args),
            'findAll' => $repository->findAll(),
            'findBy' => $repository->findBy(...$args),
            'findOneBy' => $repository->findOneBy(...$args),
            'count' => $repository->count(...$args),
            'findByCriteria' => $repository->findByCriteria(...$args),
            default => throw new InvalidArgumentException(sprintf(
                'Método %s no soportado.',
                $method
            ))
        };

        if ($expectedException === null) {

            // Asegurar que el resultado sea arreglo, y si tiene objetos dentro que
            // también sean arreglos.
            $result = json_decode(json_encode($result), true);

            $this->assertSame($expected, $result);

        }
    }

    /**
     * Prueba de repositorio con datos vacíos.
     */
    public function testEmptyRepository(): void
    {
        $repository = new Repository([], idAttribute: 'id');

        $this->assertSame(0, $repository->count());
        $this->assertSame([], $repository->findAll());
        $this->assertNull($repository->find('non-existent'));
        $this->assertNull($repository->findOneBy(['key' => 'value']));
        $this->assertSame([], $repository->findBy(['key' => 'value']));
    }

    /**
     * Prueba de repositorio con tipos de datos mixtos.
     */
    public function testMixedDataTypes(): void
    {
        $data = [
            ['id' => 1, 'name' => 'Text', 'value' => 10],
            ['id' => 2, 'name' => 42, 'value' => true],
            ['id' => 3, 'name' => null, 'value' => 3.14],
        ];

        $repository = new Repository($data, idAttribute: 'id');

        $this->assertSame(3, $repository->count());

        $foundItem = $repository->find(1); // find() busca por el índice de los datos.

        $this->assertNotNull($foundItem);
        $this->assertSame(42, $foundItem->name);
    }
}
