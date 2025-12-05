<?php

declare(strict_types=1);

/**
 * Derafu: Support - Essential PHP Utilities.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\TestsSupport\Hydrator;

use Derafu\Support\Hydrator;
use Derafu\Support\Str;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Hydrator::class)]
#[CoversClass(Str::class)]
class Hydrator1Test extends TestCase
{
    public function testHydrateWithProperties(): void
    {
        $data = ['name' => 'Alice', 'age' => 28];
        $instance = new HydratableClass();

        Hydrator::hydrate($instance, $data);

        $this->assertSame('Alice', $instance->name);
        $this->assertSame(28, $instance->age);
    }

    public function testHydrateWithSetAttributeMethod(): void
    {
        $data = ['name' => 'Bob', 'age' => 35];
        $instance = new HydratorHelperFallbackClass();

        Hydrator::hydrate($instance, $data);

        $this->assertSame('Bob', $instance->getAttribute('name'));
        $this->assertSame(35, $instance->getAttribute('age'));
    }

    public function testHydrateWithSetterMethods(): void
    {
        $data = ['name' => 'Charlie', 'age' => 40];
        $instance = new HydratorHelperSetterClass();

        Hydrator::hydrate($instance, $data);

        $this->assertSame('Charlie', $instance->getName());
        $this->assertSame(40, $instance->getAge());
    }
}

class HydratableClass
{
    public string $name;

    public int $age;
}

class HydratorHelperFallbackClass
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

class HydratorHelperSetterClass
{
    private string $name;

    private int $age;

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function setAge(int $age): void
    {
        $this->age = $age;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getAge(): int
    {
        return $this->age;
    }
}
