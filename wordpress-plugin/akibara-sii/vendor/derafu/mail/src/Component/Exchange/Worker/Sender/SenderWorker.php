<?php

declare(strict_types=1);

/**
 * Derafu: Mail - Elegant orchestration of email communications for PHP.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Mail\Component\Exchange\Worker\Sender;

use Derafu\Backbone\Abstract\AbstractWorker;
use Derafu\Backbone\Attribute\Worker;
use Derafu\Backbone\Contract\HandlerInterface;
use Derafu\Mail\Component\Exchange\Worker\Sender\Contract\SenderWorkerInterface;
use Derafu\Mail\Component\Exchange\Worker\Sender\Handler\SendHandler;
use Derafu\Mail\Model\Contract\PostmanInterface;

/**
 * Worker for sending emails.
 */
#[Worker(name: 'sender', component: 'exchange', package: 'mail')]
class SenderWorker extends AbstractWorker implements SenderWorkerInterface
{
    /**
     * Constructor of the worker with its dependencies.
     *
     * @param array|iterable<HandlerInterface> $handlers
     */
    public function __construct(iterable $handlers)
    {
        $this->setHandlers($handlers);
    }

    /**
     * {@inheritDoc}
     */
    public function send(PostmanInterface $postman): array
    {
        // Get the handler that will orchestrate the sending process.
        $handler = $this->getHandler('send');
        assert($handler instanceof SendHandler);

        // Delegate the responsibility to the handler.
        return $handler->handle($postman);
    }
}
