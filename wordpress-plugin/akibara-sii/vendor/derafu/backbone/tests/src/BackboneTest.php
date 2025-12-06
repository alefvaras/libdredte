<?php

declare(strict_types=1);

/**
 * Derafu: Backbone - The Architectural Spine for PHP Libraries.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\TestsBackbone;

use Derafu\Backbone\Abstract\AbstractWorker;
use Derafu\ExamplesBackbone\Kernel;
use Derafu\ExamplesBackbone\PackageRegistry;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PackageRegistry::class)]
#[CoversClass(AbstractWorker::class)]
class BackboneTest extends TestCase
{
    private PackageRegistry $packageRegistry;

    protected function setUp(): void
    {
        $kernel = new Kernel('dev');
        $this->packageRegistry = $kernel->getPackageRegistry();
    }

    public function testExample(): void
    {
        $job = $this
            ->packageRegistry
            ->getExamplePackage()
            ->getExampleComponent()
            ->getExampleWorker()
            ->getExampleJob()
        ;

        $result = $job->execute();

        $this->assertSame('Hello, world!', $result);
    }
}
