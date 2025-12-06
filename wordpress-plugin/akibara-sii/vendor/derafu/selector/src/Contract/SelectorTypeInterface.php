<?php

declare(strict_types=1);

/**
 * Derafu: Selector - Elegant Data Structure Navigation for PHP.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Selector\Contract;

/**
 * Interface that all selector types must implement.
 *
 * This interface defines the contract for all selector types in the system.
 * Each selector type represents a different way of accessing or manipulating
 * data within an array structure.
 */
interface SelectorTypeInterface
{
    /**
     * Reads a value from the data structure using this selector.
     *
     * @param array $data The data structure to read from.
     * @return mixed The value found at the selector's path.
     */
    public function read(array $data): mixed;

    /**
     * Writes a value to the data structure using this selector.
     *
     * @param array $data The data structure to modify.
     * @param mixed $value The value to write.
     * @return void
     */
    public function write(array &$data, mixed $value): void;

    /**
     * Clears (removes) a value from the data structure at this selector's path.
     *
     * @param array $data The data structure to modify.
     * @return void
     */
    public function clear(array &$data): void;
}
