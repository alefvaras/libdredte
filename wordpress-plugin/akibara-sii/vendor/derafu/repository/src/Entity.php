<?php

declare(strict_types=1);

/**
 * Derafu: Repository - Lightweight File Data Source Management for PHP.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Repository;

use Derafu\Repository\Contract\EntityInterface;
use Derafu\Repository\Exception\EntityException;

/**
 * Generic class for repository entity management.
 *
 * This class is useful when you don't want to explicitly create each class for
 * each entity. However, its use is discouraged and it's recommended to create
 * classes for each required entity.
 */
class Entity implements EntityInterface
{
    /**
     * Entity attributes.
     *
     * @var array
     */
    private array $attributes = [];

    /**
     * {@inheritDoc}
     */
    public function __toString(): string
    {
        return static::class . '@' . spl_object_id($this);
    }

    /**
     * {@inheritDoc}
     */
    public function toArray(): array
    {
        return $this->attributes;
    }

    /**
     * {@inheritDoc}
     */
    public function setAttribute(string $name, mixed $value): static
    {
        $this->attributes[$name] = $value;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getAttribute(string $name): mixed
    {
        if (!$this->hasAttribute($name)) {
            throw new EntityException(sprintf(
                'Attribute %s does not exist in entity %s.',
                $name,
                static::class
            ));
        }

        return $this->attributes[$name];
    }

    /**
     * {@inheritDoc}
     */
    public function hasAttribute(string $name): bool
    {
        return array_key_exists($name, $this->attributes);
    }

    /**
     * {@inheritDoc}
     */
    public function unsetAttribute(string $name): void
    {
        unset($this->attributes[$name]);
    }

    /**
     * Magic method to assign a value to an attribute as if it were defined in
     * the class.
     *
     * Executed when writing data to inaccessible (protected or private) or
     * non-existent properties.
     *
     * @param string $name
     * @param mixed $value
     * @return void
     */
    public function __set(string $name, mixed $value): void
    {
        $this->setAttribute($name, $value);
    }

    /**
     * Magic method to get the value of an attribute as if it were defined in
     * the class.
     *
     * Used to query data from inaccessible (protected or private) or
     * non-existent properties.
     *
     * @param string $name
     * @return mixed
     */
    public function __get(string $name): mixed
    {
        return $this->getAttribute($name);
    }

    /**
     * Magic method to check if an attribute exists and has a value, as if it
     * were defined in the class.
     *
     * Triggered when calling isset() or empty() on inaccessible (protected or
     * private) or non-existent properties.
     *
     * @param string $name
     * @return boolean
     */
    public function __isset(string $name): bool
    {
        return $this->hasAttribute($name);
    }

    /**
     * Magic method to unassign the value of an attribute as if it were defined
     * in the class.
     *
     * Invoked when using unset() on inaccessible (protected or private) or
     * non-existent properties.
     *
     * @param string $name
     * @return void
     */
    public function __unset(string $name): void
    {
        $this->setAttribute($name, null);
    }

    /**
     * Triggered when invoking an inaccessible method in an object context.
     *
     * Specifically processes calls to "accessors" ("getters") and "mutators"
     * ("setters").
     *
     * @param string $name
     * @param array $arguments
     * @return mixed
     */
    public function __call(string $name, array $arguments)
    {
        // If it's an "accessor" getXyz() it's processed.
        $pattern = '/^get([A-Z][a-zA-Z0-9]*)$/';
        if (preg_match($pattern, $name, $matches)) {
            return $this->getAttribute(lcfirst($matches[1]));
        }

        // If it's a "mutator" setXyz() it's processed.
        $pattern = '/^set([A-Z][a-zA-Z0-9]*)$/';
        if (preg_match($pattern, $name, $matches)) {
            return $this->setAttribute(lcfirst($matches[1]), ...$arguments);
        }

        // If the method doesn't exist an exception is generated.
        throw new EntityException(sprintf(
            'Method %s::%s() does not exist.',
            get_debug_type($this),
            $name,
        ));
    }

    /**
     * Triggered when invoking an inaccessible method in a static context.
     *
     * @param string $name
     * @param array $arguments
     * @return mixed
     */
    public static function __callStatic(string $name, array $arguments)
    {
        // If the method doesn't exist an exception is generated.
        throw new EntityException(sprintf(
            'Method %s::%s() does not exist.',
            static::class,
            $name,
        ));
    }
}
