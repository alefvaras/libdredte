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

use Derafu\Mail\Model\Contract\EnvelopeInterface;
use Derafu\Mail\Model\Contract\MessageInterface;
use Symfony\Component\Mailer\Envelope as SymfonyEnvelope;
use Symfony\Component\Mime\Email as SymfonyEmail;

/**
 * Class that represents an envelope with email messages.
 */
class Envelope extends SymfonyEnvelope implements EnvelopeInterface
{
    /**
     * Messages that the envelope contains to be sent by email.
     *
     * @var MessageInterface[]
     */
    private array $messages;

    /**
     * {@inheritDoc}
     */
    public function addMessage(MessageInterface $message): static
    {
        assert($message instanceof SymfonyEmail);

        $from = $message->getFrom();
        $sender = $message->getSender();

        if (!$from && !$sender) {
            $message->from($this->getSender());
        }

        $to = $message->getTo();
        $cc = $message->getCc();
        $bcc = $message->getBcc();

        if (!$to && !$cc && !$bcc) {
            $message->to(...$this->getRecipients());
        }

        $this->messages[] = $message;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getMessages(): array
    {
        return $this->messages;
    }

    /**
     * When the object is cloned, the messages that the envelope contained are
     * removed.
     *
     * This way the envelope of Symfony will be "clean", without messages.
     *
     * @return void
     */
    public function __clone(): void
    {
        $this->messages = [];
    }
}
