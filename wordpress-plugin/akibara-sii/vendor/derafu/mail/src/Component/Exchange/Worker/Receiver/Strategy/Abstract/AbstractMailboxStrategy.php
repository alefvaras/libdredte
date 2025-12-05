<?php

declare(strict_types=1);

/**
 * Derafu: Mail - Elegant orchestration of email communications for PHP.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Mail\Component\Exchange\Worker\Receiver\Strategy\Abstract;

use Derafu\Backbone\Abstract\AbstractStrategy;
use Derafu\Config\Contract\OptionsInterface;
use Derafu\Mail\Component\Exchange\Worker\Receiver\Strategy\Contract\ReceiverStrategyInterface;
use Derafu\Mail\Exception\MailException;
use Derafu\Mail\Model\Contract\MailboxInterface;
use Derafu\Mail\Model\Contract\PostmanInterface;
use Derafu\Mail\Model\Factory\EnvelopeFactory;
use Derafu\Mail\Model\Mailbox;
use Exception;

/**
 * Base strategy for receiving emails using a Mailbox.
 */
abstract class AbstractMailboxStrategy extends AbstractStrategy implements ReceiverStrategyInterface
{
    /**
     * Constructor de la estrategia con sus dependencias.
     *
     * @param EnvelopeFactory $envelopeFactory
     */
    public function __construct(protected readonly EnvelopeFactory $envelopeFactory)
    {
    }

    /**
     * {@inheritDoc}
     */
    public function receive(PostmanInterface $postman): array
    {
        $options = $this->resolveOptions($postman->getOptions());

        $mailbox = $this->createMailbox($options);

        $criteria = $options->get('transport.search.criteria');
        $markAsSeen = $options->get('transport.search.markAsSeen');
        $attachmentFilters = $options->get('transport.search.attachmentFilters')->all();

        try {
            // Obtener los ID de los correos según el criterio de búsqueda.
            $mailsIds = $mailbox->searchMailbox($criteria);

            // Obtener cada correo mediante su ID y armar el sobre para el
            // cartero. Los correos nunca se marcan acá como leídos. Se procesan
            // todos antes de marcarlos como leídos por si algo falla al
            // procesar.
            foreach ($mailsIds as $mailId) {
                $mail = $mailbox->getMail($mailId, markAsSeen: false);
                $envelope = $this->envelopeFactory->createFromIncomingMail(
                    $mail,
                    $attachmentFilters
                );
                $postman->addEnvelope($envelope);
            }

            // Marcar los mensajes como leídos si así se solicitó.
            if ($markAsSeen) {
                $mailbox->markMailsAsRead($mailsIds);
            }
        } catch (Exception $e) {
            throw new MailException(
                message: sprintf(
                    'An error occurred while receiving the emails: %s',
                    $e->getMessage()
                ),
                previous: $e
            );
        }

        return $postman->getEnvelopes();
    }

    /**
     * Creates a mailbox.
     *
     * @param OptionsInterface $options Options for the mailbox.
     * @return MailboxInterface
     */
    protected function createMailbox(OptionsInterface $options): MailboxInterface
    {
        $dsn = $this->resolveDsn($options);
        $username = $options->get('transport.username');
        $password = $options->get('transport.password');

        $mailbox = new Mailbox($dsn, $username, $password);

        $this->resolveEndpoint($options);

        return $mailbox;
    }

    /**
     * Resolves the DSN for the mailbox.
     *
     * @param OptionsInterface $options Options for the mailbox.
     * @return string DSN.
     */
    protected function resolveDsn(OptionsInterface $options): string
    {
        $transportOptions = $options->get('transport');

        if (empty($transportOptions['dsn'])) {
            throw new MailException('DSN is not defined for the Mailbox.');
        }

        return $transportOptions['dsn'];
    }

    /**
     * Resolves the custom endpoint.
     *
     * @param OptionsInterface $options Options for the mailbox.
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
