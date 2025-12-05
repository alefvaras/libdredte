<?php

declare(strict_types=1);

/**
 * Derafu: Translation - Translation Library with Exception Support.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Translation\Exception\Logic;

use Derafu\Translation\Contract\TranslatableInterface;
use Derafu\Translation\Trait\TranslatableExceptionTrait;
use OutOfRangeException;

/**
 * Translatable version of OutOfRangeException.
 */
class TranslatableOutOfRangeException extends OutOfRangeException implements TranslatableInterface
{
    use TranslatableExceptionTrait;
}
