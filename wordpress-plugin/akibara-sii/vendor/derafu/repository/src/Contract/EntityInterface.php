<?php

declare(strict_types=1);

/**
 * Derafu: Repository - Lightweight File Data Source Management for PHP.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Repository\Contract;

use Stringable;

/**
 * Interface for repository entities.
 */
interface EntityInterface extends Stringable
{
    /**
     * Returns entity properties as an array.
     *
     * @return array
     */
    public function toArray(): array;

    /**
     * Assign an attribute to the entity.
     *
     * @param string $name
     * @param mixed $value
     * @return static
     */
    public function setAttribute(string $name, mixed $value): static;

    /**
     * Get an attribute from the entity.
     *
     * @param string $name
     * @return mixed
     */
    public function getAttribute(string $name): mixed;

    /**
     * Allows knowing if an attribute is defined or not for the entity.
     *
     * @return bool
     */
    public function hasAttribute(string $name): bool;

    /**
     * Allows unassigning the value of an entity attribute.
     *
     * @param string $name
     * @return void
     */
    public function unsetAttribute(string $name): void;
}
