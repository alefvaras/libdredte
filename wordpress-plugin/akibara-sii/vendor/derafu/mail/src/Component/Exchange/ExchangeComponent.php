<?php

declare(strict_types=1);

/**
 * Derafu: Mail - Elegant orchestration of email communications for PHP.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Mail\Component\Exchange;

use Derafu\Backbone\Abstract\AbstractComponent;
use Derafu\Backbone\Attribute\Component;
use Derafu\Mail\Component\Exchange\Contract\ExchangeComponentInterface;
use Derafu\Mail\Component\Exchange\Worker\Receiver\Contract\ReceiverWorkerInterface;
use Derafu\Mail\Component\Exchange\Worker\Sender\Contract\SenderWorkerInterface;

/**
 * Mail component.
 *
 * Manages emails. Both sending using SenderWorker and receiving using
 * ReceiverWorker.
 */
#[Component(name: 'exchange', package: 'mail')]
class ExchangeComponent extends AbstractComponent implements ExchangeComponentInterface
{
    /**
     * Constructor of the service with its dependencies.
     *
     * @param ReceiverWorkerInterface $receiverWorker
     * @param SenderWorkerInterface $senderWorker
     */
    public function __construct(
        private ReceiverWorkerInterface $receiverWorker,
        private SenderWorkerInterface $senderWorker
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function getWorkers(): array
    {
        return [
            'receiver' => $this->receiverWorker,
            'sender' => $this->senderWorker,
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function getReceiverWorker(): ReceiverWorkerInterface
    {
        return $this->receiverWorker;
    }

    /**
     * {@inheritDoc}
     */
    public function getSenderWorker(): SenderWorkerInterface
    {
        return $this->senderWorker;
    }
}
