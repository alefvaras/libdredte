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

use ReflectionClass;
use ReflectionProperty;

/**
 * Object manipulation utility class.
 *
 * Provides functionality for working with object instances, including
 * property manipulation, reflection utilities, and object information.
 */
final class Obj
{
    /**
     * Fills public properties of an object with data from an array.
     *
     * @param object $instance Object instance to fill.
     * @param array $data Data to populate the object with.
     * @return object The filled object instance.
     */
    public static function fill(object $instance, array $data): object
    {
        $properties = array_keys(self::getPublicProperties($instance));

        foreach ($properties as $property) {
            if (array_key_exists($property, $data)) {
                $instance->$property = $data[$property];
            }
        }

        return $instance;
    }

    /**
     * Gets all public properties of an object.
     *
     * @param object $instance Object instance.
     * @return array<string,ReflectionProperty> Array of public properties.
     */
    public static function getPublicProperties(object $instance): array
    {
        $reflection = new ReflectionClass($instance);
        $properties = $reflection->getProperties(ReflectionProperty::IS_PUBLIC);

        return array_combine(
            array_map(fn ($prop) => $prop->getName(), $properties),
            $properties
        );
    }

    /**
     * Gets the class name of an object.
     *
     * @param object $instance Object instance.
     * @return string Full class name.
     */
    public static function getClassName(object $instance): string
    {
        return get_class($instance);
    }

    /**
     * Creates a ReflectionClass instance for an object.
     *
     * @param object $instance Object instance.
     * @return ReflectionClass Reflection of the object's class.
     */
    public static function getReflection(object $instance): ReflectionClass
    {
        return new ReflectionClass($instance);
    }

    /**
     * Checks if an object has a specific property.
     *
     * @param object $instance Object instance.
     * @param string $property Property name.
     * @param bool $public Whether to check only public properties.
     * @return bool True if the property exists.
     */
    public static function hasProperty(
        object $instance,
        string $property,
        bool $public = true
    ): bool {
        $reflection = new ReflectionClass($instance);

        return $reflection->hasProperty($property)
            && (!$public || $reflection->getProperty($property)->isPublic())
        ;
    }

    /**
     * Gets an array of all public property values.
     *
     * @param object $instance Object instance.
     * @return array Array of property values.
     */
    public static function getPublicValues(object $instance): array
    {
        return get_object_vars($instance);
    }
}
