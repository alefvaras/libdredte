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

use Derafu\Mail\Component\Exchange\Worker\Sender\Contract\SenderWorkerInterface;
use Derafu\Mail\Component\Exchange\Worker\Sender\Handler\SendHandler;
use Derafu\Mail\Component\Exchange\Worker\Sender\SenderWorker;
use Derafu\Mail\Component\Exchange\Worker\Sender\Strategy\SmtpStrategy;
use Derafu\Mail\Model\Envelope;
use Derafu\Mail\Model\Message;
use Derafu\Mail\Model\Postman;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mime\Address;

#[CoversClass(SenderWorker::class)]
#[CoversClass(SendHandler::class)]
#[CoversClass(SmtpStrategy::class)]
#[CoversClass(Envelope::class)]
#[CoversClass(Message::class)]
#[CoversClass(Postman::class)]
class SendMailTest extends TestCase
{
    private SenderWorkerInterface $senderWorker;

    protected function setUp(): void
    {
        $this->senderWorker = new SenderWorker([
            'send' => new SendHandler([
                'smtp' => new SmtpStrategy(),
            ]),
        ]);
    }

    public function testSendMail(): void
    {
        $username = getenv('MAIL_USERNAME');
        $password = getenv('MAIL_PASSWORD');
        $from = $username;
        $to = getenv('MAIL_TO') ?: $from;

        if (!$username || !$password) {
            $this->markTestSkipped('Does not exist configuration to send email.');
        }

        $postman = new Postman([
            'transport' => [
                'username' => $username,
                'password' => $password,
            ],
        ]);

        $envelope = new Envelope(new Address($from), [new Address($to)]);

        $message = new Message();
        $message->subject('Hello World!');
        $message->text('This is a test email.');

        $envelope->addMessage($message);
        $postman->addEnvelope($envelope);

        $this->senderWorker->send($postman);

        if ($message->hasError()) {
            $this->fail($message->getError()->getMessage());
        }

        $this->assertFalse($message->hasError());
    }
}
