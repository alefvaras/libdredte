<?php

declare(strict_types=1);

/**
 * Derafu: Mail - Elegant orchestration of email communications for PHP.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Mail;

use Derafu\Backbone\Abstract\AbstractPackage;
use Derafu\Backbone\Attribute\Package;
use Derafu\Mail\Component\Exchange\Contract\ExchangeComponentInterface;
use Derafu\Mail\Contract\MailPackageInterface;

/**
 * Mail package.
 */
#[Package(name: 'mail')]
class MailPackage extends AbstractPackage implements MailPackageInterface
{
    /**
     * Constructor of the package with its dependencies.
     */
    public function __construct(
        private readonly ExchangeComponentInterface $exchange
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function getComponents(): array
    {
        return [
            'exchange' => $this->exchange,
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function getExchangeComponent(): ExchangeComponentInterface
    {
        return $this->exchange;
    }
}
