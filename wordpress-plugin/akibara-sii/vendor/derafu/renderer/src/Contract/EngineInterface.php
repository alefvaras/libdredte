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

use Derafu\Renderer\Exception\RendererException;

interface EngineInterface
{
    /**
     * Renders a template with the given data.
     *
     * @param string $template Path or name of the template to render.
     * @param array<string,mixed> $data Variables to pass to the template.
     * @param array<string,mixed> $options Additional options for the engine.
     * @return string The rendered content.
     * @throws RendererException If rendering fails.
     */
    public function render(
        string $template,
        array $data = [],
        array $options = []
    ): string;

    /**
     * Returns the file extensions supported by this engine.
     *
     * @return array<string> List of supported file extensions (without dot).
     */
    public function getSupportedExtensions(): array;

    /**
     * Returns the name of this engine.
     *
     * @return string The engine name.
     */
    public function getName(): string;
}
