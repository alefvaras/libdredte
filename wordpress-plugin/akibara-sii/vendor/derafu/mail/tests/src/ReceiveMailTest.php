<?php

declare(strict_types=1);

/**
 * Derafu: Mail - Elegant orchestration of email communications for PHP.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\TestsMail;

use DateTimeImmutable;
use Derafu\Mail\Component\Exchange\Worker\Receiver\Contract\ReceiverWorkerInterface;
use Derafu\Mail\Component\Exchange\Worker\Receiver\Handler\ReceiveHandler;
use Derafu\Mail\Component\Exchange\Worker\Receiver\ReceiverWorker;
use Derafu\Mail\Component\Exchange\Worker\Receiver\Strategy\ImapStrategy;
use Derafu\Mail\Model\Envelope;
use Derafu\Mail\Model\Factory\EnvelopeFactory;
use Derafu\Mail\Model\Mailbox;
use Derafu\Mail\Model\Message;
use Derafu\Mail\Model\Postman;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ReceiverWorker::class)]
#[CoversClass(ReceiveHandler::class)]
#[CoversClass(ImapStrategy::class)]
#[CoversClass(EnvelopeFactory::class)]
#[CoversClass(Envelope::class)]
#[CoversClass(Mailbox::class)]
#[CoversClass(Message::class)]
#[CoversClass(Postman::class)]
class ReceiveMailTest extends TestCase
{
    private ReceiverWorkerInterface $receiverWorker;

    protected function setUp(): void
    {
        $this->receiverWorker = new ReceiverWorker([
            'receive' => new ReceiveHandler([
                'imap' => new ImapStrategy(new EnvelopeFactory()),
            ]),
        ]);
    }

    public function testReceiveMail(): void
    {
        $username = getenv('MAIL_USERNAME');
        $password = getenv('MAIL_PASSWORD');

        if (!$username || !$password) {
            $this->markTestSkipped('Does not exist configuration to receive email.');
        }

        $yesterday = (new DateTimeImmutable('yesterday'))->format('Y-m-d');

        $postman = new Postman([
            'transport' => [
                'username' => $username,
                'password' => $password,
                'search' => [
                    'criteria' => 'UNSEEN SINCE ' . $yesterday,
                ],
            ],
        ]);

        $envelopes = $this->receiverWorker->receive($postman);

        $this->assertIsArray($envelopes);
        $this->assertNotEmpty($envelopes);
    }
}
