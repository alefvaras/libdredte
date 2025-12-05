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
 * Defines the environment configuration and structure of an application.
 *
 * The Environment represents where and how an application is configured,
 * including:
 *
 *   - Directory structure.
 *   - Environment settings (dev/prod).
 *   - Configuration loading.
 *   - Application parameters.
 */
interface EnvironmentInterface
{
    /**
     * Local development environment.
     *
     * Used for development on local machines with full debugging and
     * development tools.
     *
     * @var string
     */
    public const LOCAL = 'local';

    /**
     * Development environment.
     *
     * Used for development with shared resources and integration testing.
     *
     * @var string
     */
    public const DEVELOPMENT = 'dev';

    /**
     * Testing environment.
     *
     * Used for automated tests and CI/CD pipelines.
     *
     * @var string
     */
    public const TEST = 'test';

    /**
     * Staging environment.
     *
     * Production-like environment for final testing before production
     * deployment.
     *
     * @var string
     */
    public const STAGING = 'staging';

    /**
     * Quality Assurance environment.
     *
     * Used by QA teams for manual testing and verification.
     */
    public const QUALITY_ASSURANCE = 'qa';

    /**
     * Pre-production environment.
     *
     * Final verification environment, identical to production.
     */
    public const PREPRODUCTION = 'preprod';

    /**
     * Production environment.
     *
     * Live environment serving real users.
     */
    public const PRODUCTION = 'prod';

    /**
     * Gets the current environment name.
     *
     * Common values include 'dev', 'prod', 'test', but any string is valid.
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Gets whether debug mode is enabled.
     *
     * @return bool
     */
    public function isDebug(): bool;

    /**
     * Gets the project's root directory.
     *
     * This is typically where the composer.json file is located.
     *
     * @return string
     */
    public function getProjectDir(): string;

    /**
     * Gets the application's cache directory.
     *
     * The path includes the current environment name to allow
     * environment-specific caching.
     *
     * @return string
     */
    public function getCacheDir(): string;

    /**
     * Gets the configuration directory.
     *
     * This directory typically contains:
     *
     *   - parameters.{yaml,php}
     *   - services.{yaml,php}
     *   - routes.{yaml,php}
     *
     * @return string
     */
    public function getConfigDir(): string;

    /**
     * Gets the logs directory.
     *
     * @return string
     */
    public function getLogDir(): string;

    /**
     * Gets the resources directory.
     *
     * Contains application resources like:
     *
     *   - Templates.
     *   - Assets.
     *   - Translations.
     *
     * @return string
     */
    public function getResourcesDir(): string;

    /**
     * Gets the context of the environment.
     *
     * @return array
     */
    public function getContext(): array;

    /**
     * Gets an environment variable.
     *
     * @param string $name The variable name.
     * @param mixed $default The default value if the variable is not set.
     * @return mixed
     */
    public function getEnv(string $name, mixed $default = null): mixed;

    /**
     * Gets all environment variables.
     *
     * @return array<string, string>
     */
    public function getEnvVars(): array;

    /**
     * Config from the environment.
     *
     * @return array
     */
    public function toArray(): array;
}
