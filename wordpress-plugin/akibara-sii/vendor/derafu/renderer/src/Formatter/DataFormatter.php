<?php

declare(strict_types=1);

/**
 * Derafu: Renderer - Unified Template Rendering Made Simple For PHP.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Renderer\Formatter;

use Derafu\Renderer\Contract\FormatterInterface;
use Derafu\Renderer\Contract\HandlerFormatterInterface;
use Derafu\Renderer\Exception\FormatterException;
use Derafu\Renderer\Formatter\Handler\GenericFormatterHandler;
use Throwable;

/**
 * Main formatter service that coordinates different format handlers.
 */
final class DataFormatter implements FormatterInterface
{
    /**
     * Generic handler when the handler is not an HandlerFormatterInterface and
     * cannot be resolved.
     *
     * @var GenericFormatterHandler
     */
    private GenericFormatterHandler $genericHandler;

    /**
     * Creates a new DataFormatter.
     *
     * @param array<string,string|array|object> $handlers
     */
    public function __construct(private array $handlers = [])
    {
    }

    /**
     * {@inheritDoc}
     */
    public function registerHandler(
        string $name,
        string|array|object $handler
    ): static {
        $this->handlers[$name] = $handler;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function format(mixed $value, string $format): string
    {
        // Handle null values as empty string.
        if ($value === null) {
            return '';
        }

        [$handlerName, $specificFormat] = $this->resolveHandler($format);
        $handler = $this->handlers[$handlerName] ?? $handlerName;

        try {
            // Use the handler instance.
            if ($handler instanceof HandlerFormatterInterface) {
                return $handler->handle($value, $specificFormat);
            }

            // If the handler is another type of handler resolve with the
            // generic handler.
            return $this->getGenericHandler()->handle($value, $handler, $format);
        } catch (Throwable $e) {
            throw FormatterException::forHandler($format, $e->getMessage());
        }
    }

    /**
     * Resolves the handler and specific format of the handler.
     *
     * @param string $format
     * @return array
     */
    private function resolveHandler(string $format): array
    {
        // Format is the handler.
        if (isset($this->handlers[$format])) {
            return [$format, $format];
        }

        // Split format into handler and specific format if dot notation is used.
        if (str_contains($format, '.')) {
            return explode('.', $format, 2);
        }

        // Search for a handler that supports the format.
        return [$this->determineHandler($format), $format];
    }

    /**
     * Determines the appropriate handler for a given format.
     *
     * @param string $format
     * @return string|null
     */
    private function determineHandler(string $format): ?string
    {
        foreach ($this->handlers as $name => $handler) {
            if (!$handler instanceof HandlerFormatterInterface) {
                continue;
            }
            if (in_array($format, $handler->getSupportedFormats(), true)) {
                return $name;
            }
        }

        if (isset($this->handlers['default'])) {
            return 'default';
        }

        return null;
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
