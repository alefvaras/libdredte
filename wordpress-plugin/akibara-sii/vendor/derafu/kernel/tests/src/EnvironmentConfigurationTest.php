<?php

declare(strict_types=1);

/**
 * Derafu: Kernel - Lightweight Kernel Implementation with Container.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\TestsKernel;

use Derafu\Kernel\Config\Loader\PhpRoutesLoader;
use Derafu\Kernel\Environment;
use Derafu\Kernel\MicroKernel;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ReflectionObject;
use stdClass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

#[CoversClass(MicroKernel::class)]
#[CoversClass(Environment::class)]
#[CoversClass(PhpRoutesLoader::class)]
class EnvironmentConfigurationTest extends TestCase
{
    private TestEnvironmentWithEnv $environment;

    private TestKernelWithEnv $kernel;

    protected function setUp(): void
    {
        // Set up test environment variables.
        $_ENV['DATABASE_HOST'] = 'test_db_host';
        $_ENV['DATABASE_USER'] = 'test_db_user';
        $_ENV['DATABASE_PASSWORD'] = 'test_db_password';
        $_ENV['API_KEY'] = 'test_api_key';
        $_ENV['API_URL'] = 'https://api.test.com';

        $this->environment = new TestEnvironmentWithEnv('test', true);
        $this->kernel = new TestKernelWithEnv($this->environment);
    }

    protected function tearDown(): void
    {
        // Clean up environment variables.
        unset($_ENV['DATABASE_HOST']);
        unset($_ENV['DATABASE_USER']);
        unset($_ENV['DATABASE_PASSWORD']);
        unset($_ENV['API_KEY']);
        unset($_ENV['API_URL']);
    }

    public function testEnvironmentVariablesInServiceConfiguration(): void
    {
        $container = $this->kernel->getContainer();

        // Check that services using environment variables are properly configured.
        $this->assertTrue($container->has('database.connection'));
        $this->assertTrue($container->has('api.client'));

        $dbConnection = $container->get('database.connection');
        $this->assertInstanceOf(stdClass::class, $dbConnection);

        $apiClient = $container->get('api.client');
        $this->assertInstanceOf(stdClass::class, $apiClient);
    }

    public function testEnvironmentVariablesAsContainerParameters(): void
    {
        $container = $this->kernel->getContainer();

        // Check that environment variables are available as container parameters.
        $this->assertTrue($container->hasParameter('env.DATABASE_HOST'));
        $this->assertTrue($container->hasParameter('env.DATABASE_USER'));
        $this->assertTrue($container->hasParameter('env.DATABASE_PASSWORD'));
        $this->assertTrue($container->hasParameter('env.API_KEY'));
        $this->assertTrue($container->hasParameter('env.API_URL'));

        $this->assertSame('test_db_host', $container->getParameter('env.DATABASE_HOST'));
        $this->assertSame('test_db_user', $container->getParameter('env.DATABASE_USER'));
        $this->assertSame('test_db_password', $container->getParameter('env.DATABASE_PASSWORD'));
        $this->assertSame('test_api_key', $container->getParameter('env.API_KEY'));
        $this->assertSame('https://api.test.com', $container->getParameter('env.API_URL'));
    }

    public function testEnvironmentVariablePrecedenceInContainer(): void
    {
        // Set a variable in both $_ENV and $_SERVER.
        $_ENV['TEST_VAR'] = 'env_value';
        $_SERVER['TEST_VAR'] = 'server_value';

        // Create a new kernel instance to ensure fresh environment loading.
        $newEnvironment = new TestEnvironmentWithEnv('test', true);
        $newKernel = new TestKernelWithEnv($newEnvironment);
        $container = $newKernel->getContainer();

        // Should use $_ENV value.
        $this->assertSame('env_value', $container->getParameter('env.TEST_VAR'));

        // Clean up.
        unset($_ENV['TEST_VAR']);
        unset($_SERVER['TEST_VAR']);
    }

    public function testMissingEnvironmentVariableWithDefault(): void
    {
        $environment = $this->environment;

        $this->assertSame('default_value', $environment->getEnv('MISSING_VAR', 'default_value'));
        $this->assertNull($environment->getEnv('ANOTHER_MISSING_VAR'));
    }
}

class TestEnvironmentWithEnv extends Environment
{
    public function getConfigDir(): string
    {
        return dirname(__DIR__) . '/fixtures/config';
    }

    public function getProjectDir(): string
    {
        return dirname(__DIR__) . '/fixtures';
    }
}

class TestKernelWithEnv extends MicroKernel
{
    protected const CONFIG_FILES = [
        'services_with_env.php' => 'php',
    ];

    public function getContainer(): ContainerInterface
    {
        return parent::getContainer();
    }

    protected function buildContainer(): ContainerInterface
    {
        // Force rebuild container to ensure environment variables are loaded.
        $container = new ContainerBuilder();

        // Set kernel parameters.
        foreach ($this->environment->toArray() as $name => $value) {
            $container->setParameter('kernel.' . $name, $value);
        }

        // Set environment variables as parameters.
        foreach ($this->environment->getEnvVars() as $name => $value) {
            $container->setParameter('env.' . $name, $value);
        }

        // Create the delegating loader for configuration files.
        $delegatingLoader = $this->getDelegatingLoader($container);

        // Get the kernel's own configuration file for initialization.
        $configureContainer = new ReflectionObject($this);
        $file = $configureContainer->getFileName();

        // Resolve the loader for PHP files.
        /** @var \Symfony\Component\DependencyInjection\Loader\PhpFileLoader $kernelLoader */
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
}
