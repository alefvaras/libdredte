<?php

declare(strict_types=1);

/**
 * Derafu: Renderer - Unified Template Rendering Made Simple For PHP.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Renderer\Abstract;

use Derafu\Renderer\Contract\HandlerFormatterInterface;
use Derafu\Renderer\Exception\FormatterException;
use Derafu\Renderer\Formatter\Handler\GenericFormatterHandler;

/**
 * Base class for handler formatters using a lazy map of handlers created with
 * the createHandlers() method.
 */
abstract class AbstractHandlerFormatter implements HandlerFormatterInterface
{
    /**
     * Map of handlers for data formats.
     *
     * @var array
     */
    private array $handlers;

    /**
     * Generic handler to process the formats returned by the createHandlers().
     *
     * @var GenericFormatterHandler
     */
    private GenericFormatterHandler $genericHandler;

    /**
     * {@inheritDoc}
     */
    public function handle(mixed $value, string $format): string
    {
        $handler = $this->getHandlers()[$format] ?? null;

        if ($handler === null) {
            throw new FormatterException(sprintf(
                'Handler for the format "%s" not found.',
                $format
            ));
        }

        if (is_string($handler) && str_contains($handler, 'alias:')) {
            $alias = str_replace('alias:', '', $handler);
            return $this->handle($value, $alias);
        }

        return $this->getGenericHandler()->handle($value, $handler);
    }

    /**
     * {@inheritDoc}
     */
    public function getSupportedFormats(): array
    {
        return array_keys($this->getHandlers());
    }

    /**
     * Creates the map of fields to handlers.
     *
     * @return array
     */
    abstract protected function createHandlers(): array;

    /**
     * Returns the map of handlers for data formats.
     *
     * The array of handlers is created lazily to avoid loading them in an
     * unnecessary way.
     *
     * @return array
     */
    private function getHandlers(): array
    {
        if (!isset($this->handlers)) {
            $this->handlers = $this->createHandlers();
        }

        return $this->handlers;
    }

    /**
     * Returns the generic handler.
     *
     * @return GenericFormatterHandler
     */
    private function getGenericHandler(): GenericFormatterHandler
    {
        if (!isset($this->genericHandler)) {
            $this->genericHandler = new GenericFormatterHandler();
        }

        return $this->genericHandler;
    }
}
