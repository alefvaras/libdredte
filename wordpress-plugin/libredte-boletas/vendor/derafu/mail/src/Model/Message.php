<?php

declare(strict_types=1);

/**
 * Derafu: Mail - Elegant orchestration of email communications for PHP.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Mail\Model;

use Derafu\Mail\Model\Contract\MessageInterface;
use Symfony\Component\Mime\Email as SymfonyEmail;
use Throwable;

/**
 * Class that represents an email message.
 */
class Message extends SymfonyEmail implements MessageInterface
{
    /**
     * Unique identifier of the message (regarding the transport context).
     *
     * @var int
     */
    private int $id;

    /**
     * Error that occurred during the transport of the message.
     *
     * @var Throwable|null
     */
    private ?Throwable $error = null;

    /**
     * {@inheritDoc}
     */
    public function id(int $id): static
    {
        $this->id = $id;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getId(): int
    {
        return $this->id ?? 0;
    }

    /**
     * {@inheritDoc}
     */
    public function error(Throwable $error): static
    {
        $this->error = $error;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getError(): ?Throwable
    {
        return $this->error;
    }

    /**
     * {@inheritDoc}
     */
    public function hasError(): bool
    {
        return $this->error !== null;
    }
}
