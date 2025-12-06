<?php

declare(strict_types=1);

/**
 * Derafu: Mail - Elegant orchestration of email communications for PHP.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Mail\Component\Exchange\Worker\Sender\Handler;

use Derafu\Backbone\Abstract\AbstractHandler;
use Derafu\Backbone\Attribute\Handler;
use Derafu\Mail\Component\Exchange\Worker\Sender\Strategy\Contract\SenderStrategyInterface;
use Derafu\Mail\Exception\MailException;
use Derafu\Mail\Model\Contract\PostmanInterface;
use Throwable;

/**
 * Handler for sending emails.
 */
#[Handler(name: 'send', worker: 'sender', component: 'exchange', package: 'mail')]
class SendHandler extends AbstractHandler
{
    /**
     * Constructor of the handler with its dependencies.
     *
     * @param array|iterable<SenderStrategyInterface> $strategies
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
            'default' => 'smtp',
        ],
        'transport' => [
            'types' => 'array',
            'default' => [],
        ],
    ];

    /**
     * Handles the email sending process.
     *
     * @param PostmanInterface $postman The postman with envelope information.
     * @return array The sent envelopes.
     * @throws MailException If an error occurs during sending.
     */
    public function handle(PostmanInterface $postman): array
    {
        $options = $this->resolveOptions($postman->getOptions());
        $strategy = $this->getStrategy($options->get('strategy'));

        assert($strategy instanceof SenderStrategyInterface);

        try {
            $envelopes = $strategy->send($postman);
        } catch (Throwable $e) {
            throw new MailException(
                message: $e->getMessage(),
                previous: $e
            );
        }

        return $envelopes;
    }
}
