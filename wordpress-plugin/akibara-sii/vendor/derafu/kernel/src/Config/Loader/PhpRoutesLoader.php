<?php

declare(strict_types=1);

/**
 * Derafu: Kernel - Lightweight Kernel Implementation with Container.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Kernel\Config\Loader;

use Derafu\Kernel\Trait\RoutesSanitizerTrait;
use InvalidArgumentException;
use Symfony\Component\Config\FileLocatorInterface;
use Symfony\Component\Config\Loader\FileLoader;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Loads routes configuration from PHP files.
 *
 * This loader allows defining routes in PHP files using a syntax similar to
 * the YAML configuration but with the flexibility of PHP.
 */
class PhpRoutesLoader extends FileLoader
{
    use RoutesSanitizerTrait;

    public function __construct(
        private ContainerBuilder $container,
        protected FileLocatorInterface $locator
    ) {
        parent::__construct($locator);
    }

    /**
     * Loads a PHP route configuration file.
     *
     * @param mixed $resource The resource.
     * @param string|null $type The resource type.
     * @return array The route configuration.
     */
    public function load($resource, ?string $type = null): mixed
    {
        $routes = require $resource;

        if (!is_array($routes)) {
            throw new InvalidArgumentException(sprintf(
                'The PHP file "%s" must return an array, got %s.',
                $resource,
                get_debug_type($routes)
            ));
        }

        $routes = $this->sanitizeRoutes($routes);
        $this->container->setParameter('routes', $routes);

        return $routes;
    }

    /**
     * Checks whether this loader can load the given resource.
     *
     * @param mixed $resource A resource.
     * @param string|null $type The resource type.
     * @return bool True if this loader can load the resource.
     */
    public function supports($resource, ?string $type = null): bool
    {
        return is_string($resource)
            && 'php' === pathinfo($resource, PATHINFO_EXTENSION)
            && 'routes' === $type
        ;
    }
}
