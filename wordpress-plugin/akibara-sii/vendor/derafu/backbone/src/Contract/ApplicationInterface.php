<?php

declare(strict_types=1);

/**
 * Derafu: Backbone - The Architectural Spine for PHP Libraries.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Backbone\Contract;

/**
 * Interface for the application of the application.
 */
interface ApplicationInterface
{
    /**
     * Returns the package registry of the application.
     *
     * @return PackageRegistryInterface The package registry.
     */
    public function getPackageRegistry(): PackageRegistryInterface;
}
