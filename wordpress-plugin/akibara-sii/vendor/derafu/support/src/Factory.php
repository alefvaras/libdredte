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
use stdClass;

/**
 * Object factory utility class.
 *
 * Provides functionality to create and populate object instances from arrays.
 * If no specific class is provided, creates a stdClass instance.
 */
final class Factory
{
    /**
     * Creates an instance of a class and populates it with data.
     *
     * If no class is specified, creates a stdClass instance.
     * For non-stdClass instances, uses Hydrator to populate the object.
     *
     * @template T of object
     * @param array $data Data to populate the object with.
     * @param class-string<T>|null $class Class to instantiate (null for stdClass).
     * @return T The instantiated and populated object.
     * @throws LogicException If object creation fails.
     */
    public static function create(array $data, ?string $class = null): object
    {
        $class = $class ?? stdClass::class;

        if ($class === stdClass::class) {
            return (object)$data;
        }

        return Hydrator::createAndHydrate($class, $data);
    }

    /**
     * Creates multiple instances of a class from an array of data sets.
     *
     * @template T of object
     * @param array $dataSet Array of data arrays, each used to create one instance.
     * @param class-string<T>|null $class Class to instantiate (null for stdClass).
     * @return array<T> Array of instantiated and populated objects.
     * @throws LogicException If any object creation fails.
     */
    public static function createMany(array $dataSet, ?string $class = null): array
    {
        return array_map(
            fn ($data) => self::create($data, $class),
            $dataSet
        );
    }

    /**
     * Creates an instance and ensures it's of a specific type.
     *
     * @template T of object
     * @param array $data Data to populate the object with.
     * @param class-string<T> $class Class to instantiate.
     * @param class-string<T> $expectedType Class that the instance must be.
     * @return T The instantiated and populated object.
     * @throws LogicException If object creation fails or type check fails.
     */
    public static function createAndEnsureType(
        array $data,
        string $class,
        string $expectedType
    ): object {
        $instance = self::create($data, $class);

        if (!($instance instanceof $expectedType)) {
            throw new LogicException(sprintf(
                'Created instance of %s does not match expected type %s.',
                $class,
                $expectedType
            ));
        }

        return $instance;
    }
}
