<?php

declare(strict_types=1);

/**
 * Derafu: Kernel - Lightweight Kernel Implementation with Container.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Kernel;

use Derafu\Kernel\Config\Loader\PhpRoutesLoader;
use Derafu\Kernel\Contract\EnvironmentInterface;
use Derafu\Kernel\Contract\KernelInterface;
use Derafu\Support\File;
use ReflectionObject;
use RuntimeException;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Loader\DelegatingLoader;
use Symfony\Component\Config\Loader\LoaderResolver;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Dumper\PhpDumper;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

/**
 * A lightweight kernel implementation that provides basic container and
 * configuration management.
 *
 * This kernel serves as the core of the application, managing:
 *
 *   - Dependency injection container initialization and configuration.
 */
class MicroKernel implements KernelInterface
{
    /**
     * The mapping of configuration files to their loader types.
     *
     * Array where keys are filenames and values are loader types.
     *
     * This array defines which configuration files should be loaded and with
     * which loader type. Override this constant to customize the configuration
     * files for your application.
     *
     * Default supported types:
     *
     *   - 'php': PHP configuration files.
     *   - 'yaml': YAML configuration files.
     *   - 'routes': Route configuration files (both PHP and YAML).
     *
     * @var array<string,string>
     */
    protected const CONFIG_FILES = [
        'services.php' => 'php',
        'routes.php' => 'routes',
    ];

    /**
     * Loaders for file configurations.
     *
     * @var class-string[]
     */
    protected const CONFIG_LOADERS = [
        PhpFileLoader::class,
        PhpRoutesLoader::class,
    ];

    /**
     * The current environment.
     *
     * @var EnvironmentInterface
     */
    protected EnvironmentInterface $environment;

    /**
     * Whether the kernel has been booted.
     *
     * @var bool
     */
    protected bool $booted = false;

    /**
     * The dependency injection container instance.
     *
     * @var ContainerInterface
     */
    private ContainerInterface $container;

    /**
     * Creates a new Kernel instance.
     *
     * @param string|EnvironmentInterface $environment The environment. Can be
     * an instance of EnvironmentInterface or just the name (e.g., 'dev', 'prod').
     * @param bool $debug Whether to enable debug mode. Used when $environment
     * is a string.
     */
    public function __construct(string|EnvironmentInterface $environment, bool $debug = false)
    {
        $this->environment = $environment instanceof EnvironmentInterface
            ? $environment
            : new Environment($environment, $debug)
        ;
    }

    /**
     * {@inheritDoc}
     */
    public function boot(): void
    {
        if ($this->booted) {
            return;
        }

        $containerDumpFile = $this->environment->getCacheDir() . '/container.php';
        if ($this->environment->isDebug() || !file_exists($containerDumpFile)) {
            $container = $this->buildContainer();
            $this->cacheContainer($containerDumpFile, $container);
        }

        require_once $containerDumpFile;

        // @phpstan-ignore-next-line
        $this->container = new \CachedContainer();

        $this->booted = true;
    }

    /**
     * Gets the dependency injection container.
     *
     * Boots the kernel if it hasn't been booted yet.
     *
     * @return ContainerInterface The service container.
     */
    protected function getContainer(): ContainerInterface
    {
        if (!$this->booted) {
            $this->boot();
        }

        return $this->container;
    }

    /**
     * Caches the container by dumping its configuration to a PHP file.
     *
     * This method dumps the container configuration to a PHP file that can be
     * loaded directly in subsequent requests, improving performance by avoiding
     * container rebuilding.
     *
     * The method performs two main tasks:
     *
     *   1. Dumps the container configuration using PhpDumper.
     *   2. Writes the dumped configuration to a file, either using the File
     *      utility if available, or falling back to native PHP file operations.
     *
     * @param string $file The path where the cached container should be written.
     * @param ContainerInterface $container The container instance to be cached.
     *
     * @throws RuntimeException If the cache directory cannot be created.
     * @throws RuntimeException If the container cannot be written to the cache file.
     */
    protected function cacheContainer(
        string $file,
        ContainerInterface $container
    ): void {
        assert($container instanceof ContainerBuilder);

        $dumper = new PhpDumper($container);

        $content = $dumper->dump([
            'class' => 'CachedContainer',
        ]);

        if (class_exists(File::class)) {
            File::write($file, $content);
        } else {
            $directory = dirname($file);
            if (!is_dir($directory)) {
                if (false === @mkdir($directory, 0777, true) && !is_dir($directory)) {
                    throw new RuntimeException(sprintf(
                        'Unable to create directory (%s).',
                        $directory
                    ));
                }
            }

            file_put_contents($file, $content);
        }
    }

    /**
     * Builds and configures the dependency injection container.
     *
     * This method:
     *
     *   1. Creates a new container builder.
     *   2. Sets up basic parameters.
     *   3. Creates and configures loaders.
     *   4. Loads configuration files.
     *   5. Compiles the container.
     *
     * @return ContainerInterface The configured container.
     */
    protected function buildContainer(): ContainerInterface
    {
        // Initialize container and set basic parameters.
        $container = new ContainerBuilder();

        // Set kernel parameters.
        foreach ($this->environment->toArray() as $name => $value) {
            if ($name === 'env_vars') {
                continue;
            }

            if ($name === 'context') {
                // Replace % with %% in all values.
                // This is necessary to avoid issues with the Symfony parser,
                // avoiding the interpolation of the values with the % sign as
                // parameters.
                array_walk_recursive($value, function (&$contextValue) {
                    if (is_string($contextValue)) {
                        $contextValue = str_replace('%', '%%', $contextValue);
                    }
                });
            }

            $container->setParameter('kernel.' . $name, $value);
        }

        // Set environment variables as parameters.
        // And do the same replacement for the % sign as context values.
        $envVars = $this->environment->getEnvVars();
        array_walk_recursive($envVars, function (&$envValue) {
            if (is_string($envValue)) {
                $envValue = str_replace('%', '%%', $envValue);
            }
        });
        foreach ($envVars as $name => $value) {
            $container->setParameter('env.' . $name, $value);
        }

        // Create the delegating loader for configuration files.
        $delegatingLoader = $this->getDelegatingLoader($container);

        // Get the kernel's own configuration file for initialization.
        $configureContainer = new ReflectionObject($this);
        $file = $configureContainer->getFileName();

        // Resolve the loader for PHP files.
        /** @var PhpFileLoader $kernelLoader */
        $kernelLoader = $delegatingLoader->getResolver()->resolve($file);

        // Create the container configurator.
        $instanceof = [];
        $configurator = new ContainerConfigurator(
            $container,
            $kernelLoader,
            $instanceof,
            $this->environment->getConfigDir(),
            $this->environment->getCacheDir(),
            $this->environment->getName()
        );

        // Load all configuration files.
        $this->loadConfiguration($delegatingLoader);

        // Configure the container with additional settings.
        $this->configureContainer($configurator, $container);

        // Compile the container for performance.
        $container->compile();

        return $container;
    }

    /**
     * Creates and returns a delegating loader for configuration files.
     *
     * Supports both PHP and YAML configuration files through appropriate
     * loaders.
     *
     * @param ContainerBuilder $container The container being built.
     * @return DelegatingLoader The configured loader.
     */
    protected function getDelegatingLoader(
        ContainerBuilder $container
    ): DelegatingLoader {
        $fileLocator = new FileLocator($this->environment->getConfigDir());
        $loaders = [];
        foreach (static::CONFIG_LOADERS as $configLoader) {
            $loaders[] = new $configLoader($container, $fileLocator);
        }
        $loaderResolver = new LoaderResolver($loaders);

        return new DelegatingLoader($loaderResolver);
    }

    /**
     * Loads configuration from supported config files.
     *
     * @param DelegatingLoader $loader
     * @return void
     */
    protected function loadConfiguration(DelegatingLoader $loader): void
    {
        foreach (static::CONFIG_FILES as $file => $type) {
            $configFile = $this->environment->getConfigDir() . '/' . $file;
            if (file_exists($configFile)) {
                $loader->load($configFile, $type);
            }
        }
    }

    /**
     * Configures the dependency injection container with default settings.
     *
     * Sets up:
     *
     *   - Default service configuration (autowiring, autoconfiguration).
     *   - Kernel service registration.
     *   - Additional custom configuration through configure() method.
     *
     * @param ContainerConfigurator $configurator The container configurator.
     */
    protected function configureContainer(
        ContainerConfigurator $configurator,
        ContainerBuilder $container
    ): void {
        $services = $configurator->services();

        // Set up default service configuration.
        $services
            ->defaults()
            ->autowire()
            ->autoconfigure()
            ->private()
        ;

        // Allow additional configuration through the configure method.
        $this->configure($configurator, $container);
    }

    /**
     * Hook method for additional container configuration.
     *
     * Override this method in child classes to add custom service
     * configuration previous compilation.
     *
     * @param ContainerConfigurator $configurator The container configurator.
     * @param ContainerBuilder $container The container builder.
     */
    protected function configure(
        ContainerConfigurator $configurator,
        ContainerBuilder $container
    ): void {
        // Override this method to add custom service configuration.
    }
}
