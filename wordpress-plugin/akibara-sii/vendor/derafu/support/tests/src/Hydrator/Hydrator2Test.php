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
use LogicException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(Hydrator::class)]
#[CoversClass(Str::class)]
class Hydrator2Test extends TestCase
{
    #[Test]
    public function shouldHydratePublicProperties(): void
    {
        $data = [
            'publicProperty' => 'value',
            'intProperty' => 42,
        ];

        $object = new class () {
            public string $publicProperty;

            public int $intProperty;
        };

        $result = Hydrator::hydrate($object, $data);

        $this->assertSame('value', $result->publicProperty);
        $this->assertSame(42, $result->intProperty);
    }

    #[Test]
    public function shouldHydratePrivateProperties(): void
    {
        $data = ['privateProperty' => 'private value'];

        $object = new class () {
            /** @phpstan-ignore-next-line */
            private string $privateProperty;

            public function getPrivateProperty(): string
            {
                return $this->privateProperty;
            }
        };

        $result = Hydrator::hydrate($object, $data);

        $this->assertSame('private value', $result->getPrivateProperty());
    }

    #[Test]
    public function shouldHydrateUsingSetters(): void
    {
        $data = ['name' => 'Test Name'];

        $object = new class () {
            private string $name;

            public function setName(string $name): void
            {
                $this->name = strtoupper($name);
            }

            public function getName(): string
            {
                return $this->name;
            }
        };

        $result = Hydrator::hydrate($object, $data);

        $this->assertSame('TEST NAME', $result->getName());
    }

    #[Test]
    public function shouldHydrateUsingAttributeSetters(): void
    {
        $data = ['title' => 'Test Title'];

        $object = new class () {
            private string $title;

            public function setTitleAttribute(string $title): void
            {
                $this->title = "Title: {$title}";
            }

            public function getTitle(): string
            {
                return $this->title;
            }
        };

        $result = Hydrator::hydrate($object, $data);

        $this->assertSame('Title: Test Title', $result->getTitle());
    }

    #[Test]
    public function shouldHydrateUsingGenericSetter(): void
    {
        $data = ['customField' => 'custom value'];

        $object = new class () {
            private array $attributes = [];

            public function setAttribute(string $key, mixed $value): void
            {
                $this->attributes[$key] = $value;
            }

            public function getAttribute(string $key): mixed
            {
                return $this->attributes[$key] ?? null;
            }
        };

        $result = Hydrator::hydrate($object, $data);

        $this->assertSame('custom value', $result->getAttribute('customField'));
    }

    #[Test]
    public function shouldFailOnImpossibleHydration(): void
    {
        $data = ['nonexistentProperty' => 'value'];

        $object = new class () {
            /** @phpstan-ignore-next-line */
            private string $otherProperty;
        };

        $this->expectException(LogicException::class);
        Hydrator::hydrate($object, $data);
    }

    #[Test]
    public function shouldCreateAndHydrateObject(): void
    {
        $data = ['name' => 'Test'];

        $class = new class () {
            public string $name;
        };

        $result = Hydrator::createAndHydrate(get_class($class), $data);

        $this->assertInstanceOf(get_class($class), $result);
        $this->assertSame('Test', $result->name);
    }

    #[Test]
    public function shouldFailOnInvalidClass(): void
    {
        $this->expectException(LogicException::class);
        /** @phpstan-ignore-next-line */
        Hydrator::createAndHydrate('NonExistentClass', []);
    }

    #[Test]
    public function shouldIgnoreNonExistentSetters(): void
    {
        $data = [
            'withSetter' => 'value1',
            'withoutSetter' => 'value2',
        ];

        $object = new class () {
            private string $withSetter;

            public function setWithSetter(string $value): void
            {
                $this->withSetter = $value;
            }

            public function getWithSetter(): string
            {
                return $this->withSetter;
            }
        };

        $this->expectException(LogicException::class);
        Hydrator::hydrate($object, $data);
    }

    #[Test]
    public function shouldHydrateNestedProperties(): void
    {
        $data = [
            'parent' => [
                'child' => 'value',
            ],
        ];

        $object = new class () {
            public array $parent;
        };

        $result = Hydrator::hydrate($object, $data);

        $this->assertSame(['child' => 'value'], $result->parent);
    }

    #[Test]
    public function shouldPreservePropertyTypes(): void
    {
        $data = [
            'intValue' => '42',
            'floatValue' => '3.14',
            'boolValue' => '1',
        ];

        $object = new class () {
            public int $intValue;

            public float $floatValue;

            public bool $boolValue;
        };

        $result = Hydrator::hydrate($object, $data);

        $this->assertIsInt($result->intValue);
        $this->assertIsFloat($result->floatValue);
        $this->assertIsBool($result->boolValue);
    }
}
