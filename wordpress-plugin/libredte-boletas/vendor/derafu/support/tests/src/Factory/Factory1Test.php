<?php

declare(strict_types=1);

/**
 * Derafu: Support - Essential PHP Utilities.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\TestsSupport\Factory;

use Derafu\Support\Factory;
use Derafu\Support\Hydrator;
use Derafu\Support\Str;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use stdClass;

#[CoversClass(Factory::class)]
#[CoversClass(Hydrator::class)]
#[CoversClass(Str::class)]
class Factory1Test extends TestCase
{
    public function testCreateStdClass(): void
    {
        $data = ['name' => 'John', 'age' => 30];
        $instance = Factory::create($data);

        $this->assertInstanceOf(stdClass::class, $instance);
        $this->assertSame('John', $instance->name);
        $this->assertSame(30, $instance->age);
    }

    public function testCreateFactoryHelperCustomClass(): void
    {
        $data = ['name' => 'Jane', 'age' => 25];

        $instance = Factory::create($data, FactoryHelperCustomClass::class);

        $this->assertInstanceOf(FactoryHelperCustomClass::class, $instance);
        $this->assertSame('Jane', $instance->name);
        $this->assertSame(25, $instance->age);
    }

    public function testSetAttributeFallback(): void
    {
        $data = ['name' => 'John', 'age' => 30];

        $instance = Factory::create($data, FactoryHelperFallbackClass::class);

        $this->assertInstanceOf(FactoryHelperFallbackClass::class, $instance);
        $this->assertSame('John', $instance->getAttribute('name'));
        $this->assertSame(30, $instance->getAttribute('age'));
    }
}

class FactoryHelperCustomClass
{
    public string $name;

    public int $age;
}

class FactoryHelperFallbackClass
{
    private array $attributes = [];

    public function setAttribute(string $name, mixed $value): void
    {
        $this->attributes[$name] = $value;
    }

    public function getAttribute(string $name): mixed
    {
        return $this->attributes[$name] ?? null;
    }
}
