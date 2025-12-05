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

use Derafu\Mail\Model\Contract\MailboxInterface;
use PhpImap\IncomingMail;
use PhpImap\Mailbox as PhpImapMailbox;
use stdClass;

/**
 * Email mailbox that will be used in the strategy that receives emails using
 * IMAP.
 */
class Mailbox implements MailboxInterface
{
    /**
     * The mailbox from the PhpImap library.
     *
     * @var PhpImapMailbox
     */
    private PhpImapMailbox $mailbox;

    /**
     * Constructor.
     *
     * @param string $imapPath The IMAP path.
     * @param string $login The login.
     * @param string $password The password.
     * @param string|null $attachmentsDir The attachments directory.
     * @param string $serverEncoding The server encoding.
     * @param bool $trimImapPath Whether to trim the IMAP path.
     * @param bool $attachmentFilenameMode Whether to use the attachment filename mode.
     */
    public function __construct(
        string $imapPath,
        string $login,
        string $password,
        ?string $attachmentsDir = null,
        string $serverEncoding = 'UTF-8',
        bool $trimImapPath = true,
        bool $attachmentFilenameMode = false
    ) {
        $this->mailbox = new PhpImapMailbox(
            $imapPath,
            $login,
            $password,
            $attachmentsDir,
            $serverEncoding,
            $trimImapPath,
            $attachmentFilenameMode
        );
    }

    /**
     * {@inheritDoc}
     */
    public function isConnected(): bool
    {
        return $this->mailbox->getImapStream() !== false;
    }

    /**
     * {@inheritDoc}
     */
    public function status(?string $folder = null): stdClass
    {
        $originalMailbox = $this->mailbox->getImapPath();

        if ($folder !== null) {
            $this->mailbox->switchMailbox($folder);
        }

        $status = $this->mailbox->statusMailbox();

        if ($folder !== null) {
            $this->mailbox->switchMailbox($originalMailbox);
        }

        return $status;
    }

    /**
     * {@inheritDoc}
     */
    public function countUnreadMails(?string $folder = null): int
    {
        $status = $this->status($folder);

        return $status->unseen ?? 0;
    }

    // -------------------------------------------------------------------------
    // From here on, the methods are from the PhpImap library.
    // -------------------------------------------------------------------------

    /**
     * {@inheritDoc}
     */
    public function getMailbox(): PhpImapMailbox
    {
        return $this->mailbox;
    }

    /**
     * {@inheritDoc}
     */
    public function searchMailbox(
        string $criteria = 'ALL',
        bool $disableServerEncoding = false
    ): array {
        return $this->mailbox->searchMailbox($criteria, $disableServerEncoding);
    }

    /**
     * {@inheritDoc}
     */
    public function getMail(int $mailId, bool $markAsSeen = true): IncomingMail
    {
        return $this->mailbox->getMail($mailId, $markAsSeen);
    }

    /**
     * {@inheritDoc}
     */
    public function markMailsAsRead(array $mailIds): void
    {
        $this->mailbox->markMailsAsRead($mailIds);
    }
}
