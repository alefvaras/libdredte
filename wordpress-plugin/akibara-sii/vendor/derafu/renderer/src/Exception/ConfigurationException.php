<?php

declare(strict_types=1);

/**
 * Derafu: Renderer - Unified Template Rendering Made Simple For PHP.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Renderer\Exception;

/**
 * Thrown when there is a configuration error.
 */
final class ConfigurationException extends RendererException
{
    public static function forMissingOption(string $option): static
    {
        return new static(sprintf(
            'Required configuration option "%s" is missing.',
            $option
        ));
    }

    public static function forInvalidOption(
        string $option,
        string $value,
        string $expected
    ): static {
        return new static(sprintf(
            'Invalid value "%s" for configuration option "%s". Expected: %s.',
            $value,
            $option,
            $expected
        ));
    }
}
