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
 * Thrown when a template cannot be found or is not readable.
 */
final class TemplateNotFoundException extends RendererException
{
    public static function forTemplate(string $template): static
    {
        return new static(sprintf(
            'Template "%s" not found or is not readable.',
            $template
        ));
    }

    public static function forPath(string $path): static
    {
        return new static(sprintf(
            'Template path "%s" not found or is not readable.',
            $path
        ));
    }
}
