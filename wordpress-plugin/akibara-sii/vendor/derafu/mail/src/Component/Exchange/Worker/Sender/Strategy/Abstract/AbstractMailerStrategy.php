<?php

declare(strict_types=1);

/**
 * Derafu: Mail - Elegant orchestration of email communications for PHP.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Mail\Component\Exchange\Worker\Sender\Strategy\Abstract;

use Derafu\Backbone\Abstract\AbstractStrategy;
use Derafu\Config\Contract\OptionsInterface;
use Derafu\Mail\Component\Exchange\Worker\Sender\Strategy\Contract\SenderStrategyInterface;
use Derafu\Mail\Exception\MailException;
use Derafu\Mail\Model\Contract\PostmanInterface;
use Exception;
use Symfony\Component\Mailer\Envelope as SymfonyEnvelope;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mime\Email as SymfonyEmail;

/**
 * Base strategy for sending emails using the Mailer of Symfony.
 */
abstract class AbstractMailerStrategy extends AbstractStrategy implements SenderStrategyInterface
{
    /**
     * {@inheritDoc}
     */
    public function send(PostmanInterface $postman): array
    {
        $options = $this->resolveOptions($postman->getOptions());

        $mailer = $this->createMailer($options);

        foreach ($postman->getEnvelopes() as $envelope) {
            assert($envelope instanceof SymfonyEnvelope);
            foreach ($envelope->getMessages() as $message) {
                assert($message instanceof SymfonyEmail);
                try {
                    $mailer->send($message, clone $envelope);
                } catch (Exception $e) {
                    $message->error($e);
                }
            }
        }

        return $postman->getEnvelopes();
    }

    /**
     * Creates an email sender.
     *
     * @param OptionsInterface $options Options for the sender.
     * @return Mailer
     */
    protected function createMailer(OptionsInterface $options): Mailer
    {
        $dsn = $this->resolveDsn($options);
        $transport = Transport::fromDsn($dsn);
        $mailer = new Mailer($transport);

        $this->resolveEndpoint($options);

        return $mailer;
    }

    /**
     * Resolves the DSN for the mailer.
     *
     * @param OptionsInterface $options Configuration for the mailer.
     * @return string DSN.
     */
    protected function resolveDsn(OptionsInterface $options): string
    {
        $transportOptions = $options->get('transport');

        if (empty($transportOptions['dsn'])) {
            throw new MailException('DSN is not defined for the Mailer.');
        }

        return $transportOptions['dsn'];
    }

    /**
     * Resolves the custom endpoint.
     *
     * @param OptionsInterface $options Configuration for the mailer.
     * @return string Custom endpoint.
     */
    protected function resolveEndpoint(OptionsInterface $options): string
    {
        $transportOptions = $options->get('transport');

        if (!empty($transportOptions['endpoint'])) {
            return $transportOptions['endpoint'];
        }

        $endpoint = $this->resolveDsn($options);

        $options->set('transport.endpoint', $endpoint);

        return $endpoint;
    }
}
