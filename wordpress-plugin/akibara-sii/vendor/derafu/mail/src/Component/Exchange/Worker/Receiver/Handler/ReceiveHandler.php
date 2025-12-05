<?php

declare(strict_types=1);

/**
 * Derafu: Mail - Elegant orchestration of email communications for PHP.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Mail\Component\Exchange\Worker\Receiver\Handler;

use Derafu\Backbone\Abstract\AbstractHandler;
use Derafu\Backbone\Attribute\Handler;
use Derafu\Mail\Component\Exchange\Worker\Receiver\Strategy\Contract\ReceiverStrategyInterface;
use Derafu\Mail\Exception\MailException;
use Derafu\Mail\Model\Contract\PostmanInterface;
use Throwable;

/**
 * Handler for receiving emails.
 */
#[Handler(name: 'receive', worker: 'receiver', component: 'exchange', package: 'mail')]
class ReceiveHandler extends AbstractHandler
{
    /**
     * Constructor of the handler with its dependencies.
     *
     * @param array|iterable<ReceiverStrategyInterface> $strategies
     */
    public function __construct(iterable $strategies)
    {
        $this->setStrategies($strategies);
    }

    /**
     * Schema of the options.
     *
     * @var array<string,array|bool>
     */
    protected array $optionsSchema = [
        'strategy' => [
            'types' => 'string',
            'default' => 'imap',
        ],
        'transport' => [
            'types' => 'array',
            'default' => [],
        ],
    ];

    /**
     * Handles the email receiving process.
     *
     * @param PostmanInterface $postman The postman with mailbox information.
     * @return array The received envelopes.
     * @throws MailException If an error occurs during reception.
     */
    public function handle(PostmanInterface $postman): array
    {
        $options = $this->resolveOptions($postman->getOptions());
        $strategy = $this->getStrategy($options->get('strategy'));

        assert($strategy instanceof ReceiverStrategyInterface);

        try {
            $envelopes = $strategy->receive($postman);
        } catch (Throwable $e) {
            throw new MailException(
                message: $e->getMessage(),
                previous: $e
            );
        }

        return $envelopes;
    }
}
