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

use Derafu\Backbone\Exception\ComponentNotFoundException;

/**
 * Interface for the packages of the application.
 */
interface PackageInterface extends ServiceInterface
{
    /**
     * Returns a component of the package.
     *
     * A component is a service that implements ComponentInterface.
     *
     * @param string $name The name of the component.
     * @return ComponentInterface The component.
     * @throws ComponentNotFoundException If the component is not registered.
     */
    public function getComponent(string $name): ComponentInterface;

    /**
     * Returns the list of components of the package.
     *
     * A component is a service that implements ComponentInterface.
     *
     * @return array<string, ComponentInterface> The list of components.
     */
    public function getComponents(): array;
}
