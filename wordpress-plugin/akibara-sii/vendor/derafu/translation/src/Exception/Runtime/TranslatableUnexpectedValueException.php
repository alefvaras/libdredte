<?php

declare(strict_types=1);

/**
 * Derafu: Translation - Translation Library with Exception Support.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Translation\Exception\Runtime;

use Derafu\Translation\Contract\TranslatableInterface;
use Derafu\Translation\Trait\TranslatableExceptionTrait;
use UnexpectedValueException;

/**
 * Translatable version of UnexpectedValueException.
 */
class TranslatableUnexpectedValueException extends UnexpectedValueException implements TranslatableInterface
{
    use TranslatableExceptionTrait;
}
