<?php

declare(strict_types=1);

/**
 * Derafu: Mail - Elegant orchestration of email communications for PHP.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Mail\Component\Exchange\Worker\Receiver\Contract;

use Derafu\Backbone\Contract\WorkerInterface;
use Derafu\Mail\Model\Contract\EnvelopeInterface;
use Derafu\Mail\Model\Contract\PostmanInterface;

/**
 * Interface for the worker "mail.exchange.receiver".
 */
interface ReceiverWorkerInterface extends WorkerInterface
{
    /**
     * Receives envelopes with messages from email using the transport options
     * defined in the postman.
     *
     * @param PostmanInterface $postman Postman for the email transport.
     * @return EnvelopeInterface[] Envelopes with messages received.
     */
    public function receive(PostmanInterface $postman): array;
}
