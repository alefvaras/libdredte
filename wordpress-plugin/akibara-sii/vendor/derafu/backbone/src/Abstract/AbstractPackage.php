<?php

declare(strict_types=1);

/**
 * Derafu: Backbone - The Architectural Spine for PHP Libraries.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Backbone\Abstract;

use Derafu\Backbone\Contract\ComponentInterface;
use Derafu\Backbone\Contract\PackageInterface;
use Derafu\Backbone\Exception\ComponentNotFoundException;
use Derafu\Config\Contract\ConfigurationInterface;
use Derafu\Config\Trait\ConfigurableTrait;

/**
 * Base class for the packages of the application.
 */
abstract class AbstractPackage extends AbstractService implements PackageInterface
{
    use ConfigurableTrait;

    /**
     * {@inheritDoc}
     */
    public function getComponent(string $name): ComponentInterface
    {
        // Get all components of the package.
        $components = $this->getComponents();

        // The component was not found.
        if (!isset($components[$name])) {
            throw ComponentNotFoundException::forComponent($name);
        }

        // Return the found component.
        return $components[$name];
    }

    /**
     * Returns the configuration of a component of the package.
     *
     * @param string $name The name of the component.
     * @return array|ConfigurationInterface
     */
    protected function getComponentConfiguration(
        string $name
    ): array|ConfigurationInterface {
        $config = $this->getConfiguration()->get('components.' . $name);

        return $config ?? [];
    }
}
