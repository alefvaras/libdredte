<?php

declare(strict_types=1);

/**
 * Derafu: Mail - Elegant orchestration of email communications for PHP.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Mail\Component\Exchange\Contract;

use Derafu\Backbone\Contract\ComponentInterface;
use Derafu\Mail\Component\Exchange\Worker\Receiver\Contract\ReceiverWorkerInterface;
use Derafu\Mail\Component\Exchange\Worker\Sender\Contract\SenderWorkerInterface;

/**
 * Interface for the email service.
 */
interface ExchangeComponentInterface extends ComponentInterface
{
    /**
     * Gets the worker "mail.exchange.receiver".
     *
     * @return ReceiverWorkerInterface
     */
    public function getReceiverWorker(): ReceiverWorkerInterface;

    /**
     * Gets the worker "mail.exchange.sender".
     *
     * @return SenderWorkerInterface
     */
    public function getSenderWorker(): SenderWorkerInterface;
}
