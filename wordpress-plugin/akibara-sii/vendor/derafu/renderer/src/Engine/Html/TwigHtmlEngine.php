<?php

declare(strict_types=1);

/**
 * Derafu: Renderer - Unified Template Rendering Made Simple For PHP.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Renderer\Engine\Html;

use Derafu\Renderer\Contract\EngineInterface;
use Derafu\Twig\Contract\TwigServiceInterface;

/**
 * Twig template engine implementation.
 */
class TwigHtmlEngine implements EngineInterface
{
    public function __construct(private TwigServiceInterface $twigService)
    {
    }

    /**
     * {@inheritDoc}
     */
    public function render(
        string $template,
        array $data = [],
        array $options = []
    ): string {
        // Merge runtime options with template data.
        $context = array_replace_recursive(['options' => $options], $data);

        // Render template with the context.
        return $this->twigService->render($template, $context);
    }

    /**
     * {@inheritDoc}
     */
    public function getSupportedExtensions(): array
    {
        return ['html.twig', 'txt.twig'];
    }

    /**
     * {@inheritDoc}
     */
    public function getName(): string
    {
        return 'twig';
    }
}
