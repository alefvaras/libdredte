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

use Derafu\Renderer\Exception\FormatterException;

/**
 * Interface for handling specific data format operations.
 */
interface HandlerFormatterInterface
{
    /**
     * Handles the formatting of a value according to the specified format.
     *
     * @param mixed $value The value to format.
     * @param string $format The format identifier.
     * @return string The formatted value.
     * @throws FormatterException If formatting fails.
     */
    public function handle(mixed $value, string $format): string;

    /**
     * Returns the list of formats supported by this handler.
     *
     * @return array<string> List of supported format identifiers.
     */
    public function getSupportedFormats(): array;
}
