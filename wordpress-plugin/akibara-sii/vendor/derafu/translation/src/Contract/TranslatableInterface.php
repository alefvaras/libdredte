<?php

declare(strict_types=1);

namespace Derafu\Translation\Contract;

use Stringable;
use Symfony\Contracts\Translation\TranslatableInterface as SymfonyTranslatableInterface;

/**
 * Extends Symfony's TranslatableInterface to provide additional functionality
 * with support for ICU message formatting using __toString()
 */
interface TranslatableInterface extends SymfonyTranslatableInterface, Stringable
{
}
