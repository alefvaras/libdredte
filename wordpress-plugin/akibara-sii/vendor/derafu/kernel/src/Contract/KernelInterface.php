<?php

declare(strict_types=1);

/**
 * Derafu: Kernel - Lightweight Kernel Implementation with Container.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Kernel\Contract;

/**
 * Interface for a very small kernel.
 */
interface KernelInterface
{
    /**
     * Boots the kernel.
     *
     * This method initializes the container and marks the kernel as booted.
     * It ensures this only happens once, even if called multiple times.
     *
     * @return void
     */
    public function boot(): void;
}
