<?php

declare(strict_types=1);

/**
 * Derafu: Mail - Elegant orchestration of email communications for PHP.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Mail\Model\Contract;

use Derafu\Config\Contract\OptionsAwareInterface;

/**
 * Interface for the "postman" that will be used to send or receive emails.
 */
interface PostmanInterface extends OptionsAwareInterface
{
    /**
     * Adds an envelope to the postman.
     *
     * @param EnvelopeInterface $envelope
     * @return static
     */
    public function addEnvelope(EnvelopeInterface $envelope): static;

    /**
     * Gets the list of envelopes that the postman has.
     *
     * @return EnvelopeInterface[]
     */
    public function getEnvelopes(): array;
}
