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

use Throwable;

/**
 * Interface for the email message.
 */
interface MessageInterface
{
    /**
     * Assigns the unique ID of the message (regarding the transport context).
     *
     * @param integer $id
     * @return static
     */
    public function id(int $id): static;

    /**
     * Gets the unique ID of the message (regarding the transport context).
     *
     * @return integer
     */
    public function getId(): int;

    /**
     * Assigns the error that occurred with the message during its transport.
     *
     * @param Throwable $error
     * @return static
     */
    public function error(Throwable $error): static;

    /**
     * Gets the error that occurred with the message during its transport.
     *
     * @return Throwable|null
     */
    public function getError(): ?Throwable;

    /**
     * Indicates if the message had any error during its transport.
     *
     * @return boolean
     */
    public function hasError(): bool;
}
