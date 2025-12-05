<?php

declare(strict_types=1);

/**
 * Derafu: Mail - Elegant orchestration of email communications for PHP.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Mail\Model\Factory;

use DateTimeImmutable;
use Derafu\Mail\Model\Contract\EnvelopeInterface;
use Derafu\Mail\Model\Contract\MessageInterface;
use Derafu\Mail\Model\Envelope;
use Derafu\Mail\Model\Message;
use PhpImap\IncomingMail;
use PhpImap\IncomingMailAttachment;
use Symfony\Component\Mime\Address;

/**
 * Factory to create an Envelope object from an IncomingMail instance.
 */
class EnvelopeFactory
{
    /**
     * Creates an envelope from the data of an incoming email.
     *
     * @param IncomingMail $mail
     * @param array $attachmentFilters
     * @return EnvelopeInterface
     */
    public function createFromIncomingMail(
        IncomingMail $mail,
        array $attachmentFilters = []
    ): EnvelopeInterface {
        // Determine who sent the email.
        if (!empty($mail->senderAddress)) {
            $senderAddress = $mail->senderAddress;
            $senderName = $mail->senderName ?? '';
        } else {
            $senderAddress = $mail->fromAddress;
            $senderName = $mail->fromName ?? '';
        }

        // Create the complete list of email recipients.
        $recipients = array_merge($mail->to, $mail->cc, $mail->bcc);

        // Create the envelope.
        $envelope = new Envelope(
            new Address($senderAddress, $senderName),
            array_map(
                fn ($email, $name) => new Address($email, $name ?? ''),
                array_keys($recipients),
                $recipients
            )
        );

        // Create the message and add it to the envelope.
        $message = $this->createMessage($mail, $attachmentFilters);
        $envelope->addMessage($message);

        // Return the envelope with the message.
        return $envelope;
    }

    /**
     * Creates the message from the data of an incoming email.
     *
     * @param IncomingMail $mail
     * @param array $attachmentFilters
     * @return MessageInterface
     */
    private function createMessage(
        IncomingMail $mail,
        array $attachmentFilters = []
    ): MessageInterface {
        // Create the message.
        $message = new Message();

        // Add the message ID (in the context of the transport).
        $message->id($mail->id);

        // Add the message date.
        $message->date(new DateTimeImmutable($mail->date));

        // Add the sender.
        $message->from(new Address($mail->fromAddress, $mail->fromName ?? ''));

        // Add the main recipients (TO).
        if (!empty($mail->to)) {
            $message->to(...array_map(
                fn ($email, $name) => new Address($email, $name ?? ''),
                array_keys($mail->to),
                $mail->to
            ));
        }

        // Add the copy recipients (CC).
        if (!empty($mail->cc)) {
            $message->cc(...array_map(
                fn ($email, $name) => new Address($email, $name ?? ''),
                array_keys($mail->cc),
                $mail->cc
            ));
        }

        // Add the hidden recipients (BCC).
        if (!empty($mail->bcc)) {
            $message->bcc(...array_map(
                fn ($email, $name) => new Address($email, $name ?? ''),
                array_keys($mail->bcc),
                $mail->bcc
            ));
        }

        // Add the subject.
        if (!empty($mail->subject)) {
            $message->subject($mail->subject);
        }

        // Add the message body as plain text.
        if (!empty($mail->textPlain)) {
            $message->text($mail->textPlain);
        }

        // Add the message body as HTML.
        if (!empty($mail->textHtml)) {
            $message->html($mail->textHtml);
        }

        // Add the attachments if they exist.
        foreach ($mail->getAttachments() as $attachment) {
            if (
                !$attachmentFilters
                || $this->attachmentPassFilters($attachment, $attachmentFilters)
            ) {
                $message->attach(
                    $attachment->getContents(),
                    $attachment->name,
                    $attachment->mimeType
                );
            }
        }

        // Return the email message.
        return $message;
    }

    /**
     * Applies the filters to a part of the message.
     *
     * @param IncomingMailAttachment $attachment Part of the message to filter.
     * @param array $filters Filters to use.
     * @return bool `true` if the part of the message passes the filters, `false`
     * otherwise.
     */
    private function attachmentPassFilters(
        IncomingMailAttachment $attachment,
        array $filters
    ): bool {
        // Filter by: subtype.
        if (!empty($filters['subtype'])) {
            $subtype = strtoupper($attachment->subtype);
            $subtypes = array_map('strtoupper', $filters['subtype']);
            if (!in_array($subtype, $subtypes)) {
                return false;
            }
        }

        // Filter by: extension.
        if (!empty($filters['extension'])) {
            $extension = strtolower(pathinfo(
                $attachment->name,
                PATHINFO_EXTENSION
            ));
            $extensions = array_map('strtolower', $filters['extension']);
            if (!in_array($extension, $extensions)) {
                return false;
            }
        }

        // Passed the filters ok.
        return true;
    }
}
