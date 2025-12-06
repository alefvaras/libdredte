<?php

declare(strict_types=1);

/**
 * Derafu: Backbone - The Architectural Spine for PHP Libraries.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Backbone\Trait;

use Derafu\Backbone\Contract\PackageInterface;
use Derafu\Backbone\Contract\PackageProviderInterface;
use Derafu\Backbone\Exception\PackageNotFoundException;

/**
 * Trait for the package registry of the application.
 */
trait PackageRegistryTrait
{
    /**
     * List of registered packages.
     *
     * @var array<string, PackageInterface>
     */
    private array $packages = [];

    /**
     * Constructor of the class.
     *
     * @param array<string, PackageInterface>|PackageProviderInterface $packages
     */
    public function __construct(
        array|PackageProviderInterface $packages
    ) {
        if ($packages instanceof PackageProviderInterface) {
            $packages = $packages->getPackages();
        }

        foreach ($packages as $name => $package) {
            $this->registerPackage($name, $package);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function registerPackage(string $name, PackageInterface $package): static
    {
        $this->packages[$name] = $package;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getPackage(string $name): PackageInterface
    {
        if (!$this->hasPackage($name)) {
            throw PackageNotFoundException::forPackage($name);
        }

        return $this->packages[$name];
    }

    /**
     * {@inheritDoc}
     */
    public function getPackages(): array
    {
        return $this->packages;
    }

    /**
     * {@inheritDoc}
     */
    public function hasPackage(string $name): bool
    {
        return isset($this->packages[$name]);
    }
}
