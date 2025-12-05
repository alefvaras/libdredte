<?php

declare(strict_types=1);

/**
 * Derafu: Backbone - The Architectural Spine for PHP Libraries.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\ExamplesBackbone;

use Derafu\Backbone\Contract\PackageRegistryInterface;
use Derafu\Kernel\MicroKernel;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class Kernel extends MicroKernel
{
    protected const CONFIG_FILES = [
        '../tests/fixtures/services.yaml' => 'yaml',
    ];

    protected const CONFIG_LOADERS = [
        PhpFileLoader::class,
        YamlFileLoader::class,
    ];

    public function getPackageRegistry(): PackageRegistry
    {
        return $this->getContainer()->get(PackageRegistryInterface::class);
    }

    protected function configure(
        ContainerConfigurator $configurator,
        ContainerBuilder $container
    ): void {
        $configurator->import(__DIR__ . '/../fixtures/services.yaml');
    }
}
