<?php

declare(strict_types=1);

/**
 * Derafu: Renderer - Unified Template Rendering Made Simple For PHP.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Renderer\Factory;

use Derafu\Markdown\Service\MarkdownService;
use Derafu\Renderer\Contract\EngineInterface;
use Derafu\Renderer\Contract\RendererInterface;
use Derafu\Renderer\Engine\Html\MarkdownHtmlEngine;
use Derafu\Renderer\Engine\Html\PhpHtmlEngine;
use Derafu\Renderer\Engine\Html\TwigHtmlEngine;
use Derafu\Renderer\Engine\Pdf\HtmlPdfEngine;
use Derafu\Renderer\Formatter\DataFormatter;
use Derafu\Renderer\Formatter\Extension\TwigFormatterExtension;
use Derafu\Renderer\Renderer;
use Derafu\Twig\Contract\TwigServiceInterface;
use Derafu\Twig\Provider\AllComponentProvider;
use Derafu\Twig\Service\TwigService;

/**
 * Factory for creating pre-configured Renderer instances.
 *
 * This factory provides a convenient way to create a fully configured Renderer
 * with selected engines and formatters. By default, it sets up Twig engine, but
 * can be configured to include others like Markdown, PHP and PDF.
 *
 * @example Basic usage with defaults (Twig):
 * ```php
 * $renderer = RendererFactory::create([
 *     'paths' => ['/path/to/templates'],
 * ]);
 * ```
 *
 * @example Custom engine selection:
 * ```php
 * $renderer = RendererFactory::create([
 *     'paths' => ['/path/to/templates'],
 *     'engines' => ['twig', 'pdf'], // Only Twig and PDF engines.
 * ]);
 * ```
 *
 * @example Full configuration:
 * ```php
 * $renderer = RendererFactory::create([
 *     'paths' => ['/path/to/templates'],
 *     'engines' => ['twig', 'markdown', 'php', 'pdf'],
 *     'formatters' => [
 *         'date' => ['format' => 'Y-m-d'],
 *         'currency' => ['decimals' => 2],
 *     ],
 *     'extensions' => [
 *         new CustomTwigExtension(),
 *     ],
 *     'extra' => true,
 *     'components' => new CustomComponentProvider(),
 * ]);
 * ```
 */
class RendererFactory
{
    /**
     * List of all available engines.
     *
     * @var array<string,string>
     */
    private const AVAILABLE_ENGINES = [
        'twig' => TwigHtmlEngine::class,
        'markdown' => MarkdownHtmlEngine::class,
        'php' => PhpHtmlEngine::class,
        'pdf' => HtmlPdfEngine::class,
    ];

    /**
     * Default engines to enable if none specified.
     *
     * @var array<string>
     */
    private const DEFAULT_ENGINES = ['twig'];

    /**
     * Creates a new pre-configured Renderer instance.
     *
     * Creates a Renderer with specified engines configured and ready to use.
     * By default, enables Twig engine. The Twig environment is set up with
     * formatters and extensions, and other engines are configured to use this
     * Twig environment for consistent rendering when needed.
     *
     * @param array{
     *   paths?: array<string>,
     *   engines?: array<string|EngineInterface>,
     *   formatters?: array<string,mixed>,
     *   extensions?: array<\Twig\Extension\ExtensionInterface>,
     *   extra?: bool,
     *   components?: \Derafu\Twig\Contract\ComponentProviderInterface
     * } $options Configuration options:
     *   - paths: Array of template directory paths
     *   - engines: Array of engine names to enable (default: ['twig'])
     *   - formatters: Array of formatter configurations
     *   - extensions: Array of additional Twig extensions
     *   - extra: Whether to enable extra Twig features
     *   - components: Component provider for Twig
     * @return RendererInterface Configured renderer instance
     */
    public static function create(array $options = []): RendererInterface
    {
        // Extract options with defaults.
        $paths = $options['paths'] ?? [];
        $enabledEngines = $options['engines'] ?? self::DEFAULT_ENGINES;
        $formatters = $options['formatters'] ?? [];
        $formatter = new DataFormatter($formatters);
        $options['formatter'] = $formatter;

        // Create and configure Twig service.
        $twigService = self::createTwigService($options);

        // Initialize engines array.
        $engines = [];

        // Create requested engines.
        foreach ($enabledEngines as $engine) {
            if ($engine instanceof EngineInterface) {
                $engines[$engine->getName()] = $engine;
            } else {
                if (!isset(self::AVAILABLE_ENGINES[$engine])) {
                    continue; // Skip unavailable engines.
                }

                $engines[$engine] = match($engine) {
                    'twig' => new TwigHtmlEngine($twigService),
                    'markdown' => new MarkdownHtmlEngine(
                        new MarkdownService(paths: $paths),
                        $twigService
                    ),
                    'php' => new PhpHtmlEngine(
                        $twigService,
                        $formatter,
                        $paths
                    ),
                    'pdf' => new HtmlPdfEngine($twigService),
                };
            }
        }

        // Ensure at least Twig engine is available as it's required by other
        // engines.
        if (!isset($engines['twig'])) {
            $engines['twig'] = new TwigHtmlEngine($twigService);
        }

        return new Renderer($engines);
    }

    /**
     * Creates a new pre-configured Twig service instance.
     *
     * Creates a TwigService with specified paths, extensions, extra features,
     * and component provider.
     *
     * @param array<string,mixed> $options Configuration options.
     * @return TwigServiceInterface Configured Twig service instance.
     */
    public static function createTwigService(array $options = []): TwigServiceInterface
    {
        $paths = $options['paths'] ?? [];
        $extensions = $options['extensions'] ?? [];
        $extra = $options['extra'] ?? true;
        $components = $options['components'] ?? new AllComponentProvider();

        if (!isset($options['formatter'])) {
            $formatters = $options['formatters'] ?? [];
            $formatter = new DataFormatter($formatters);
        } else {
            $formatter = $options['formatter'];
        }

        $extensions[] = new TwigFormatterExtension($formatter);

        return new TwigService([
            'paths' => $paths,
            'extensions' => $extensions,
            'extra' => $extra,
            'components' => $components,
        ]);
    }
}
