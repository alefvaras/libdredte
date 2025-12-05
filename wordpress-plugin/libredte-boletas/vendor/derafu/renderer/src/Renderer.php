<?php

declare(strict_types=1);

/**
 * Derafu: Renderer - Unified Template Rendering Made Simple For PHP.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Renderer;

use Derafu\Renderer\Contract\EngineInterface;
use Derafu\Renderer\Contract\RendererInterface;
use Derafu\Renderer\Exception\EngineException;

/**
 * Main renderer class that orchestrates different rendering engines.
 */
class Renderer implements RendererInterface
{
    /**
     * @var array<string, EngineInterface>
     */
    private array $engines = [];

    /**
     * Map of file extensions to engine names.
     *
     * @var array<string, string>
     */
    private array $extensionMap = [];

    /**
     * Default engine to use when no specific engine is determined.
     *
     * @var string
     */
    private string $defaultEngine;

    /**
     * @param array<string, EngineInterface> $engines Initial set of engines.
     * @param string $defaultEngine Default engine to use.
     */
    public function __construct(
        array $engines = [],
        string $defaultEngine = 'twig'
    ) {
        $this->defaultEngine = $defaultEngine;

        foreach ($engines as $name => $engine) {
            $this->addEngine($name, $engine);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function render(
        string $template,
        array $data = [],
        array $options = []
    ): string {
        // Determine which engine to use.
        $engineName = $this->determineEngine($template, $options);

        // Get the engine.
        $engine = $this->getEngine($engineName);

        // Render the template.
        return $engine->render($template, $data, $options);
    }

    /**
     * {@inheritDoc}
     */
    public function getEngine(string $name): EngineInterface
    {
        if (!isset($this->engines[$name])) {
            throw EngineException::forEngine($name);
        }

        return $this->engines[$name];
    }

    /**
     * {@inheritDoc}
     */
    public function addEngine(string $name, EngineInterface $engine): static
    {
        $this->engines[$name] = $engine;

        // Map file extensions to this engine.
        foreach ($engine->getSupportedExtensions() as $extension) {
            $this->extensionMap[$extension] = $name;
        }

        return $this;
    }

    /**
     * Determines which engine should handle the template.
     *
     * @param string $template Template name/path.
     * @param array<string,mixed> $options Rendering options.
     * @return string Name of the engine to use.
     */
    private function determineEngine(string $template, array &$options): string
    {
        // If engine is explicitly specified in options, use that engine.
        if (!empty($options['engine'])) {
            return $options['engine'];
        }

        // If format is explicitly specified in options, translate it to engine.
        if (!empty($options['format'])) {
            return $this->formatToEngine($options['format']);
        }

        // Try to determine engine from file extension.
        foreach ($this->extensionMap as $extension => $engine) {
            if (str_ends_with($template, '.' . $extension)) {
                return $engine;
            }
        }

        // Use default engine if no specific engine could be determined.
        return $this->defaultEngine;
    }

    /**
     * Translates a format to an engine name.
     *
     * @param string $format Format to translate.
     * @return string Engine name.
     */
    private function formatToEngine(string $format): string
    {
        return match ($format) {
            'html' => 'twig',
            'pdf' => 'pdf',
            default => $format,
        };
    }
}
