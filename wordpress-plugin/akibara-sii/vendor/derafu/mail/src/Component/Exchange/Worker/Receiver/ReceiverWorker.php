<?php

declare(strict_types=1);

/**
 * Derafu: Mail - Elegant orchestration of email communications for PHP.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Mail\Component\Exchange\Worker\Receiver;

use Derafu\Backbone\Abstract\AbstractWorker;
use Derafu\Backbone\Attribute\Worker;
use Derafu\Backbone\Contract\HandlerInterface;
use Derafu\Mail\Component\Exchange\Worker\Receiver\Contract\ReceiverWorkerInterface;
use Derafu\Mail\Component\Exchange\Worker\Receiver\Handler\ReceiveHandler;
use Derafu\Mail\Model\Contract\PostmanInterface;

/**
 * Worker for receiving emails.
 */
#[Worker(name: 'receiver', component: 'exchange', package: 'mail')]
class ReceiverWorker extends AbstractWorker implements ReceiverWorkerInterface
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
    public function receive(PostmanInterface $postman): array
    {
        // Get the handler that will orchestrate the receiving process
        $handler = $this->getHandler('receive');
        assert($handler instanceof ReceiveHandler);

        // Delegate the responsibility to the handler
        return $handler->handle($postman);
    }
}
