<?php

declare(strict_types=1);

/**
 * Derafu: Twig - UI Component and Extension Library.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Twig\Extension;

use Derafu\Routing\Contract\RouterInterface;
use Derafu\Routing\Enum\UrlReferenceType;
use Derafu\Routing\Exception\RouteNotFoundException;
use LogicException;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Twig extension to enable route generation within templates.
 *
 * This extension provides `path` and `url` functions to generate URLs for named
 * routes within Twig templates.
 */
class RoutingExtension extends AbstractExtension
{
    /**
     * Constructor.
     *
     * @param RouterInterface $router The router service.
     */
    public function __construct(private RouterInterface $router)
    {
    }

    /**
     * Registers custom Twig functions.
     *
     * @return array List of functions provided by this extension.
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('path', [$this, 'getPath']),
            new TwigFunction('url', [$this, 'getUrl']),
            new TwigFunction('is_active_path', [$this, 'isActivePath']),
        ];
    }

    /**
     * Generates a relative path for a route.
     *
     * @param string $name The name of the route
     * @param array $parameters Parameters to pass to the route
     * @return string The generated URL
     * @throws RouteNotFoundException If the route doesn't exist
     */
    public function getPath(string $name, array $parameters = []): string
    {
        return $this->router->generate(
            $name,
            $parameters,
            UrlReferenceType::ABSOLUTE_PATH
        );
    }

    /**
     * Generates an absolute URL for a route.
     *
     * @param string $name The name of the route
     * @param array $parameters Parameters to pass to the route
     * @param bool $schemeRelative Whether to make the URL scheme-relative
     * @return string The generated absolute URL
     * @throws RouteNotFoundException If the route doesn't exist
     */
    public function getUrl(
        string $name,
        array $parameters = [],
        bool $schemeRelative = false
    ): string {
        if ($schemeRelative) {
            return $this->router->generate(
                $name,
                $parameters,
                UrlReferenceType::NETWORK_PATH
            );
        }

        return $this->router->generate(
            $name,
            $parameters,
            UrlReferenceType::ABSOLUTE_URL
        );
    }

    /**
     * Checks if the current path matches the given route.
     *
     * @param string $name The name of the route.
     * @param array $parameters Parameters to pass to the route.
     * @param bool $partial Whether to check for partial match.
     * @return bool True if the current path matches the route, false otherwise.
     */
    public function isActivePath(
        string $name,
        array $parameters = [],
        bool $partial = true
    ): bool {
        // Get context from router.
        $context = $this->router->getContext();
        if ($context === null) {
            throw new LogicException('Routing context not set.');
        }

        // Get current path from context and route path from router.
        $currentPath = $context->getBaseUrl() . $context->getPathInfo();
        $routePath = $this->getPath($name, $parameters);

        // Normalize removing trailing slashes.
        $currentPath = rtrim($currentPath, '/');
        $routePath = rtrim($routePath, '/');

        // If partial match, compare by segments.
        if ($partial) {
            // Compare by segments.
            $contextSegments = explode('/', ltrim($currentPath, '/'));
            $routeSegments = explode('/', ltrim($routePath, '/'));

            // Only match if route is full prefix of the URI.
            return array_slice($contextSegments, 0, count($routeSegments)) === $routeSegments;
        }

        // If exact match, compare the full path.
        return $currentPath === $routePath;
    }
}
