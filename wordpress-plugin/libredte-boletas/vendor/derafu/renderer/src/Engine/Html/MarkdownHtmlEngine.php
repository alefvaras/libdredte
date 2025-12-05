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

use Derafu\Markdown\Contract\MarkdownServiceInterface;
use Derafu\Renderer\Contract\EngineInterface;
use Derafu\Twig\Contract\TwigServiceInterface;

/**
 * Markdown template engine implementation using CommonMark.
 */
class MarkdownHtmlEngine implements EngineInterface
{
    public function __construct(
        private MarkdownServiceInterface $markdownService,
        private TwigServiceInterface $twigService,
        private readonly ?string $wrapperTemplate = 'html',
        private readonly string $contentVarName = 'content'
    ) {
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
        $html = $this->markdownService->render($template, $context);

        // Render wrapper with twig template.
        $wrapperTemplate = $context['wrapperTemplate'] ?? $this->wrapperTemplate;
        $wrapperContext = array_merge(
            $context,
            [$this->contentVarName => $html]
        );

        return $this->twigService->render($wrapperTemplate, $wrapperContext);
    }

    /**
     * {@inheritDoc}
     */
    public function getSupportedExtensions(): array
    {
        return ['md', 'markdown'];
    }

    /**
     * {@inheritDoc}
     */
    public function getName(): string
    {
        return 'markdown';
    }
}
