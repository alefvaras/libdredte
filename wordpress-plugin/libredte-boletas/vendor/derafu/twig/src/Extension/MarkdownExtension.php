<?php

declare(strict_types=1);

/**
 * Derafu: Twig - UI Component and Extension Library.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Twig\Extension;

use Derafu\Markdown\Contract\MarkdownServiceInterface;
use Derafu\Markdown\Service\MarkdownService;
use LogicException;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * Twig extension to enable Markdown rendering within templates.
 *
 * This extension provides a `markdown` filter to convert Markdown content
 * into HTML using Derafu: Markdown.
 */
class MarkdownExtension extends AbstractExtension
{
    /**
     * Markdown rendering service.
     *
     * @var MarkdownServiceInterface
     */
    private MarkdownServiceInterface $markdownService;

    /**
     * Constructor.
     *
     * @param MarkdownService|null $markdownService Service for rendering Markdown.
     */
    public function __construct(
        ?MarkdownServiceInterface $markdownService = null
    ) {
        if ($markdownService === null) {
            if (class_exists(MarkdownService::class)) {
                $markdownService = new MarkdownService();
            }
        }

        if ($markdownService !== null) {
            $this->markdownService = $markdownService;
        }
    }

    /**
     * Registers custom Twig filters.
     *
     * @return array List of filters provided by this extension.
     */
    public function getFilters(): array
    {
        return [
            new TwigFilter('markdown', [$this, 'renderMarkdown'], ['is_safe' => ['html']]),
        ];
    }

    /**
     * Renders Markdown content into HTML.
     *
     * @param string $content The Markdown content to convert.
     * @return string The rendered HTML.
     */
    public function renderMarkdown(string $content): string
    {
        if (!isset($this->markdownService)) {
            throw new LogicException(
                'MarkdownExtension requires Derafu\\Markdown to be installed. Run "composer require derafu/markdown" to enable this extension.'
            );

        }

        return $this->markdownService->renderFromString($content);
    }
}
