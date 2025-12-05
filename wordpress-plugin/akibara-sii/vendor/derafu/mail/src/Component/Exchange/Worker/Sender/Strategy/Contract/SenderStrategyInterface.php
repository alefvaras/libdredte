<?php

declare(strict_types=1);

/**
 * Derafu: Mail - Elegant orchestration of email communications for PHP.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Mail\Component\Exchange\Worker\Sender\Strategy\Contract;

use Derafu\Backbone\Contract\StrategyInterface;
use Derafu\Mail\Model\Contract\EnvelopeInterface;
use Derafu\Mail\Model\Contract\PostmanInterface;

/**
 * Interface for the strategies of the sender service.
 */
interface SenderStrategyInterface extends StrategyInterface
{
    /**
     * Sends envelopes with messages through email using the transport options
     * defined in the postman.
     *
     * @param PostmanInterface $postman Postman for the email transport.
     * @return EnvelopeInterface[] Envelopes with messages sent.
     */
    public function send(PostmanInterface $postman): array;
}
