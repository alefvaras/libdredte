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

use Derafu\Config\Contract\OptionsInterface;
use Derafu\Config\Trait\OptionsAwareTrait;
use Derafu\Mail\Model\Contract\EnvelopeInterface;
use Derafu\Mail\Model\Contract\PostmanInterface;

/**
 * Class that represents an envelope with messages that will be sent by email.
 */
class Postman implements PostmanInterface
{
    use OptionsAwareTrait;

    /**
     * Envelopes that the postman will transport.
     *
     * @var EnvelopeInterface[]
     */
    private array $envelopes = [];

    /**
     * Schema of the postman options.
     *
     * @var array
     */
    protected array $optionsSchema = [];

    /**
     * Constructor of the postman.
     *
     * @param array|OptionsInterface $options
     */
    public function __construct(array|OptionsInterface $options = [])
    {
        $this->setOptions($options);
    }

    /**
     * {@inheritDoc}
     */
    public function addEnvelope(EnvelopeInterface $envelope): static
    {
        $this->envelopes[] = $envelope;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getEnvelopes(): array
    {
        return $this->envelopes;
    }
}
