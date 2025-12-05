<?php

declare(strict_types=1);

/**
 * Derafu: Backbone - The Architectural Spine for PHP Libraries.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Backbone\Contract;

use Derafu\Backbone\Exception\HandlerNotFoundException;

/**
 * Interface for the services that use handlers.
 */
interface HandlersAwareInterface
{
    /**
     * Gets a handler by its name.
     *
     * @param string $name The name of the handler.
     * @return HandlerInterface The handler.
     * @throws HandlerNotFoundException If the handler is not registered.
     */
    public function getHandler(string $name): HandlerInterface;

    /**
     * Gets all handlers.
     *
     * @return array<string, HandlerInterface> The list of handlers.
     */
    public function getHandlers(): array;

    /**
     * Sets the handlers.
     *
     * @param array<string, HandlerInterface>|iterable $handlers The list of handlers.
     * @return static The instance.
     */
    public function setHandlers(iterable $handlers): static;
}
