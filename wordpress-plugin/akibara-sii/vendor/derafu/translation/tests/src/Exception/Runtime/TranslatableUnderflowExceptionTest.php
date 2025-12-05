<?php

declare(strict_types=1);

/**
 * Derafu: Translation - Translation Library with Exception Support.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\TestsTranslation\Exception\Runtime;

use Derafu\Translation\Contract\TranslatableInterface;
use Derafu\Translation\Exception\Runtime\TranslatableUnderflowException;
use Derafu\Translation\TranslatableMessage;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Throwable;
use UnderflowException;

#[CoversClass(TranslatableUnderflowException::class)]
#[CoversClass(TranslatableMessage::class)]
final class TranslatableUnderflowExceptionTest extends TestCase
{
    public function testExceptionInheritance(): void
    {
        $exception = new TranslatableUnderflowException('Test');

        $this->assertInstanceOf(UnderflowException::class, $exception);
        $this->assertInstanceOf(Throwable::class, $exception);
        $this->assertInstanceOf(TranslatableInterface::class, $exception);
    }
}
