<?php

declare(strict_types=1);

/**
 * Derafu: Translation - Translation Library with Exception Support.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\TestsTranslation\Exception\Logic;

use Derafu\Translation\Contract\TranslatableInterface;
use Derafu\Translation\Exception\Logic\TranslatableInvalidArgumentException;
use Derafu\Translation\TranslatableMessage;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Throwable;

#[CoversClass(TranslatableInvalidArgumentException::class)]
#[CoversClass(TranslatableMessage::class)]
final class TranslatableInvalidArgumentExceptionTest extends TestCase
{
    public function testExceptionInheritance(): void
    {
        $exception = new TranslatableInvalidArgumentException('Test');

        $this->assertInstanceOf(InvalidArgumentException::class, $exception);
        $this->assertInstanceOf(Throwable::class, $exception);
        $this->assertInstanceOf(TranslatableInterface::class, $exception);
    }
}
