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

use ArrayAccess;
use ArrayIterator;
use ArrayObject;
use Closure;
use Derafu\Container\Vault;
use IteratorAggregate;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\OptionsResolver\Options;
use Traversable;

#[CoversClass(Vault::class)]
class VaultTest extends TestCase
{
    public static function provideTestCases(): array
    {
        $tests = require __DIR__ . '/../fixtures/vault.php';

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

    public function testVaultSchemaGetterAndSetter(): void
    {
        $schema = [
            'foo' => [
                'required' => true,
            ],
        ];

        $container = new Vault();
        $container->setSchema($schema);
        $this->assertSame($schema, $container->getSchema());
    }

    #[DataProvider('provideTestCases')]
    public function testVaultCase(
        array $data,
        array $schema,
        array|string $expected
    ): void {
        if (is_string($expected)) {
            $this->expectException($expected);
        }

        // Se deben resolver los normalizadores porque se pasan como string
        // desde el caso de prueba y deben ser closure para DataContainer.
        $this->resolveNormalizers($schema);

        $container = new Vault($data, $schema);

        foreach ($expected as $selector => $expectedValue) {
            $this->assertSame($expectedValue, $container->get($selector));
        }
    }

    private function resolveNormalizers(&$schema): void
    {
        foreach ($schema as $key => &$rules) {
            if (!empty($rules['normalizer'])) {
                $rules['normalizer'] = $this->resolveNormalizer(
                    $rules['normalizer']
                );
            }
            if (!empty($rules['schema'])) {
                $this->resolveNormalizers($rules['schema']);
            }
        }
    }

    private function resolveNormalizer($normalizer): Closure
    {
        switch ($normalizer) {
            case 'float':
                return fn (Options $options, $value) => (float) $value;
            default:
                return fn (Options $options, $value) => $value;
        }
    }

    // Prueba básica para asegurar que un array simple se convierta
    // correctamente.
    public function testVaultInDataContainerWithArray(): void
    {
        $data = ['key1' => 'value1', 'key2' => 'value2'];
        $container = new Vault($data);

        $this->assertSame(
            $data,
            $container->all(),
            'Error al convertir desde un array.'
        );
    }

    // Prueba con un ArrayObject estándar.
    public function testVaultInDataContainerWithArrayObject(): void
    {
        $data = new ArrayObject(['key1' => 'value1', 'key2' => 'value2']);
        $container = new Vault($data);

        $this->assertSame(
            ['key1' => 'value1', 'key2' => 'value2'],
            $container->all(),
            'Error al convertir desde ArrayObject.'
        );
    }

    // Prueba con una clase personalizada que implemente ambas interfaces.
    public function testVaultInDataContainerWithArrayAccess(): void
    {
        $data = new MyArrayAccess();
        $container = new Vault($data);

        $this->assertSame(
            ['key1' => 'value1', 'key2' => 'value2'],
            $container->all(),
            'Error al convertir desde un ArrayAccess/Traversable.'
        );
    }
}

class MyArrayAccess implements ArrayAccess, IteratorAggregate
{
    private array $container = ['key1' => 'value1', 'key2' => 'value2'];

    public function offsetExists($offset): bool
    {
        return isset($this->container[$offset]);
    }

    public function offsetGet($offset): mixed
    {
        return $this->container[$offset] ?? null;
    }

    public function offsetSet($offset, $value): void
    {
        $this->container[$offset] = $value;
    }

    public function offsetUnset($offset): void
    {
        unset($this->container[$offset]);
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->container);
    }
}
