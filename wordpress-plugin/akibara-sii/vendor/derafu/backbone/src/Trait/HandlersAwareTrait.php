<?php

declare(strict_types=1);

/**
 * Derafu: Backbone - The Architectural Spine for PHP Libraries.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Backbone\Trait;

use Derafu\Backbone\Contract\HandlerInterface;
use Derafu\Backbone\Exception\HandlerException;

/**
 * Trait for the services that use handlers.
 */
trait HandlersAwareTrait
{
    /**
     * Handlers that the service uses.
     *
     * @var HandlerInterface[]
     */
    protected array $handlers = [];

    /**
     * Gets a handler by its name.
     *
     * @param string $handler
     * @return HandlerInterface
     */
    public function getHandler(string $handler): HandlerInterface
    {
        if (!isset($this->handlers[$handler])) {
            throw new HandlerException(sprintf(
                'Handler %s not found in service %s (%s).',
                $handler,
                $this->getName(),
                $this->getId(),
            ));
        }

        return $this->handlers[$handler];
    }

    /**
     * Gets all handlers.
     *
     * @return HandlerInterface[]
     */
    public function getHandlers(): array
    {
        return $this->handlers;
    }

    /**
     * Sets the handlers.
     *
     * @param array|iterable $handlers
     * @return static
     */
    public function setHandlers(iterable $handlers): static
    {
        $this->handlers = is_array($handlers)
            ? $handlers
            : iterator_to_array($handlers)
        ;

        return $this;
    }
}
