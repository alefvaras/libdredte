<?php

declare(strict_types=1);

/**
 * Derafu: Renderer - Unified Template Rendering Made Simple For PHP.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Renderer\Contract;

interface FormatterInterface
{
    /**
     * Registers a new format handler.
     *
     * @param string $name Optional name for the handler.
     * @param string|array|object $handler The handler to register.
     * @return static
     */
    public function registerHandler(
        string $name,
        string|array|object $handler
    ): static;

    /**
     * Formats a value according to the specified format.
     *
     * @param mixed $value Value to format.
     * @param string $format Format identifier.
     * @return string
     */
    public function format(mixed $value, string $format): string;
}
