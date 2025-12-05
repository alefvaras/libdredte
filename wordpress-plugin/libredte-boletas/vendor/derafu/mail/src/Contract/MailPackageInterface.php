<?php

declare(strict_types=1);

/**
 * Derafu: Mail - Elegant orchestration of email communications for PHP.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Mail\Contract;

use Derafu\Backbone\Contract\PackageInterface;
use Derafu\Mail\Component\Exchange\Contract\ExchangeComponentInterface;

/**
 * Interface for the mail package.
 */
interface MailPackageInterface extends PackageInterface
{
    /**
     * Gets the mail component.
     */
    public function getExchangeComponent(): ExchangeComponentInterface;
}
