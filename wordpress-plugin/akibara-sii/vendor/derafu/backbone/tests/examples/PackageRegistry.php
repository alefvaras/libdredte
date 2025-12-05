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
use Derafu\Backbone\Trait\PackageRegistryTrait;

final class PackageRegistry implements PackageRegistryInterface
{
    use PackageRegistryTrait;

    public function getExamplePackage(): ExamplePackage
    {
        $package = $this->getPackage('example');
        assert($package instanceof ExamplePackage);
        return $package;
    }
}
