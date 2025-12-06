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

/**
 * Interface for the envelope with email messages.
 */
interface EnvelopeInterface
{
    /**
     * Adds an email message to the envelope.
     *
     * @param MessageInterface $message
     * @return static
     */
    public function addMessage(MessageInterface $message): static;

    /**
     * Gets the list of email messages in the envelope.
     *
     * @return MessageInterface[]
     */
    public function getMessages(): array;
}
