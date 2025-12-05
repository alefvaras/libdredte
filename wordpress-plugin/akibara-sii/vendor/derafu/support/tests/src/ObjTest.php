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

use Derafu\Support\Obj;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionProperty;

#[CoversClass(Obj::class)]
class ObjTest extends TestCase
{
    #[Test]
    public function shouldFillPublicProperties(): void
    {
        $data = [
            'publicString' => 'test',
            'publicInt' => 42,
            'publicArray' => ['value'],
        ];

        $object = new TestPublicPropertiesClass();
        $result = Obj::fill($object, $data);

        $this->assertSame('test', $result->publicString);
        $this->assertSame(42, $result->publicInt);
        $this->assertSame(['value'], $result->publicArray);

        // Private property should remain unchanged
        $reflection = new ReflectionClass($result);
        $property = $reflection->getProperty('privateProperty');
        $property->setAccessible(true);
        $this->assertSame('private', $property->getValue($result));
    }

    #[Test]
    public function shouldGetPublicProperties(): void
    {
        $object = new TestVisibilityClass();
        $properties = Obj::getPublicProperties($object);

        $this->assertCount(1, $properties);
        $this->assertArrayHasKey('publicString', $properties);
        $this->assertInstanceOf(ReflectionProperty::class, $properties['publicString']);
        $this->assertTrue($properties['publicString']->isPublic());
    }

    #[Test]
    public function shouldGetClassName(): void
    {
        $object = new TestPropertyClass();
        $className = Obj::getClassName($object);

        $this->assertSame(TestPropertyClass::class, $className);
    }

    #[Test]
    public function shouldGetReflection(): void
    {
        $object = new TestPropertyClass();
        $reflection = Obj::getReflection($object);

        $this->assertInstanceOf(ReflectionClass::class, $reflection);
        $this->assertTrue($reflection->hasProperty('property'));
    }

    #[Test]
    #[DataProvider('providePropertyTestData')]
    public function shouldCheckPropertyExistence(
        object $object,
        string $property,
        bool $public,
        bool $expected
    ): void {
        $result = Obj::hasProperty($object, $property, $public);
        $this->assertSame($expected, $result);
    }

    public static function providePropertyTestData(): array
    {
        $testObject = new TestVisibilityPropertiesClass();

        return [
            'public property exists' => [
                $testObject,
                'publicProp',
                true,
                true,
            ],
            'protected property with public check' => [
                $testObject,
                'protectedProp',
                true,
                false,
            ],
            'protected property without public check' => [
                $testObject,
                'protectedProp',
                false,
                true,
            ],
            'private property exists' => [
                $testObject,
                'privateProp',
                false,
                true,
            ],
            'nonexistent property' => [
                $testObject,
                'nonexistentProp',
                false,
                false,
            ],
        ];
    }

    #[Test]
    public function shouldGetPublicValues(): void
    {
        $object = new TestPublicValuesClass();
        $values = Obj::getPublicValues($object);

        $this->assertCount(2, $values);
        $this->assertSame([
            'first' => 'one',
            'second' => 2,
        ], $values);
    }

    #[Test]
    public function shouldPreservePropertyTypes(): void
    {
        $object = new TestPropertyTypesClass();
        $data = [
            'intValue' => 42,
            'floatValue' => 3.14,
            'stringValue' => 'test',
            'boolValue' => true,
            'arrayValue' => [1, 2, 3],
        ];

        $result = Obj::fill($object, $data);

        $this->assertIsInt($result->intValue);
        $this->assertIsFloat($result->floatValue);
        $this->assertIsString($result->stringValue);
        $this->assertIsBool($result->boolValue);
        $this->assertIsArray($result->arrayValue);
    }

    #[Test]
    public function shouldHandleNullableProperties(): void
    {
        $object = new TestNullableClass();
        $data = [
            'nullableString' => 'test',
            'nullableInt' => null,
        ];

        $result = Obj::fill($object, $data);

        $this->assertSame('test', $result->nullableString);
        $this->assertNull($result->nullableInt);
    }

    #[Test]
    public function shouldHandleInheritedProperties(): void
    {
        $object = new TestChildClass();
        $properties = Obj::getPublicProperties($object);

        $this->assertCount(2, $properties);
        $this->assertArrayHasKey('parentProperty', $properties);
        $this->assertArrayHasKey('childProperty', $properties);
    }

    #[Test]
    public function shouldNotFillNonExistentProperties(): void
    {
        $object = new TestExistingPropertyClass();
        $data = [
            'existingProperty' => 'new value',
            'nonExistentProperty' => 'test',
        ];

        $result = Obj::fill($object, $data);

        $this->assertSame('new value', $result->existingProperty);
        $this->assertFalse(property_exists($result, 'nonExistentProperty'));
    }
}

// Test classes for filling public properties.
class TestPublicPropertiesClass
{
    public string $publicString;

    public int $publicInt;

    public array $publicArray;

    /** @phpstan-ignore-next-line */
    private string $privateProperty = 'private';
}

// Test classes for visibility checks.
class TestVisibilityClass
{
    public string $publicString = 'test';

    protected int $protectedInt = 42;

    /** @phpstan-ignore-next-line */
    private array $privateArray = [];
}

// Test class for property access.
class TestPropertyClass
{
    public string $property = 'test';
}

// Test class for property types.
class TestPropertyTypesClass
{
    public int $intValue;

    public float $floatValue;

    public string $stringValue;

    public bool $boolValue;

    public array $arrayValue;
}

// Test class for nullable properties.
class TestNullableClass
{
    public ?string $nullableString = null;

    public ?int $nullableInt = 42;
}

// Test class for existing properties.
class TestExistingPropertyClass
{
    public string $existingProperty = 'original';
}

// Test class for visibility properties.
class TestVisibilityPropertiesClass
{
    public string $publicProp = 'public';

    protected string $protectedProp = 'protected';

    /** @phpstan-ignore-next-line */
    private string $privateProp = 'private';
}

// Test class for public values.
class TestPublicValuesClass
{
    public string $first = 'one';

    public int $second = 2;

    protected bool $third = true;

    /** @phpstan-ignore-next-line */
    private array $fourth = [];
}

// Test classes for inheritance.
class TestParentClass
{
    public string $parentProperty = 'parent';
}

class TestChildClass extends TestParentClass
{
    public string $childProperty = 'child';
}
