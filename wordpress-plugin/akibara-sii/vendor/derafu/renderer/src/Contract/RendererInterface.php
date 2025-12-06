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

use Derafu\Renderer\Exception\EngineException;

interface RendererInterface
{
    /**
     * Renders a template with the given data.
     *
     * @param string $template Path or name of the template to render.
     * @param array $data Data to pass to the template.
     * @param array $options Rendering options (format, config, etc).
     * @return string Rendered content.
     */
    public function render(
        string $template,
        array $data = [],
        array $options = []
    ): string;

    /**
     * Gets a registered engine by name.
     *
     * @param string $name Engine name.
     * @return EngineInterface
     * @throws EngineException If the engine is not found.
     */
    public function getEngine(string $name): EngineInterface;

    /**
     * Adds a new rendering engine.
     *
     * @param string $name Engine name.
     * @param EngineInterface $engine Engine instance.
     * @return static
     */
    public function addEngine(string $name, EngineInterface $engine): static;
}
