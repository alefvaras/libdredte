<?php

declare(strict_types=1);

/**
 * Derafu: Support - Essential PHP Utilities.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Support;

use LogicException;
use ReflectionClass;
use ReflectionException;

/**
 * Object hydration utility class.
 *
 * Provides functionality to populate object properties from arrays using reflection
 * and setter methods. Supports direct property access and various setter method
 * naming conventions.
 */
final class Hydrator
{
    /**
     * Hydrates an object with data from an array.
     *
     * The method attempts to set values in the following order:
     * 1. Direct property access if property exists
     * 2. setAttribute() method if it exists
     * 3. setXxx() method where Xxx is the studly case version of the attribute
     * 4. setXxxAttribute() method
     *
     * @param object $instance Object instance to hydrate.
     * @param array $data Data to populate the object with.
     * @return object The hydrated object instance.
     * @throws LogicException If a value cannot be assigned.
     */
    public static function hydrate(object $instance, array $data): object
    {
        $reflection = new ReflectionClass($instance);

        foreach ($data as $attribute => $value) {
            if (!self::tryAssignValue($instance, $reflection, $attribute, $value)) {
                throw new LogicException(sprintf(
                    'Cannot assign attribute "%s" to class %s. No property or suitable setter method found.',
                    $attribute,
                    $reflection->getName()
                ));
            }
        }

        return $instance;
    }

    /**
     * Attempts to assign a value to an object property through various methods.
     *
     * @param object $instance Object instance.
     * @param ReflectionClass $reflection Reflection of the object's class.
     * @param string $attribute Attribute name.
     * @param mixed $value Value to assign.
     * @return bool True if the value was assigned, false otherwise.
     */
    private static function tryAssignValue(
        object $instance,
        ReflectionClass $reflection,
        string $attribute,
        mixed $value
    ): bool {
        // Try setter methods.
        $methods = [
            'set' . Str::studly($attribute),
            'set' . Str::studly($attribute) . 'Attribute',
        ];
        foreach ($methods as $method) {
            if (method_exists($instance, $method)) {
                $instance->$method($value);
                return true;
            }
        }

        // Try setAttribute method.
        if (method_exists($instance, 'setAttribute')) {
            $instance->setAttribute($attribute, $value);
            return true;
        }

        // Try direct property assignment.
        if ($reflection->hasProperty($attribute)) {
            $property = $reflection->getProperty($attribute);
            $property->setAccessible(true);
            $property->setValue($instance, $value);
            return true;
        }

        return false;
    }

    /**
     * Creates a new instance of a class and hydrates it with data.
     *
     * @template T of object
     * @param class-string<T> $class The class to instantiate.
     * @param array $data Data to populate the object with.
     * @return T The instantiated and hydrated object.
     * @throws LogicException If the class cannot be instantiated.
     */
    public static function createAndHydrate(string $class, array $data): object
    {
        try {
            $reflection = new ReflectionClass($class);
            $instance = $reflection->newInstanceWithoutConstructor();
            return self::hydrate($instance, $data);
        } catch (ReflectionException $e) {
            throw new LogicException(
                "Failed to create instance of {$class}: {$e->getMessage()}",
                0,
                $e
            );
        }
    }
}
