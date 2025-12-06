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

use Derafu\Backbone\Exception\PackageNotFoundException;

/**
 * Interface for the package registry of the application.
 */
interface PackageRegistryInterface
{
    /**
     * Registers a package.
     *
     * @param string $name The name of the package.
     * @param PackageInterface $package The package to register.
     * @return static The registry instance.
     */
    public function registerPackage(string $name, PackageInterface $package): static;

    /**
     * Returns a registered package.
     *
     * A package is a service that implements PackageInterface.
     *
     * @param string $name The name of the package.
     * @return PackageInterface The registered package.
     * @throws PackageNotFoundException If the package is not registered.
     */
    public function getPackage(string $name): PackageInterface;

    /**
     * Returns the list of registered packages.
     *
     * A package is a service that implements PackageInterface.
     *
     * @return array<string, PackageInterface>
     */
    public function getPackages(): array;

    /**
     * Checks if a package is registered.
     *
     * @param string $name The name of the package.
     * @return bool Whether the package is registered.
     */
    public function hasPackage(string $name): bool;
}
