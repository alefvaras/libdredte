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

use Closure;
use Derafu\Renderer\Contract\EngineInterface;
use Derafu\Renderer\Contract\FormatterInterface;
use Derafu\Renderer\Exception\TemplateNotFoundException;
use Derafu\Twig\Contract\TwigServiceInterface;
use Throwable;

/**
 * PHP native template engine implementation.
 */
class PhpHtmlEngine implements EngineInterface
{
    public function __construct(
        private TwigServiceInterface $twigService,
        private readonly FormatterInterface $formatter,
        private readonly array $paths = [],
        private readonly string $wrapperTemplate = 'html',
        private readonly string $contentVarName = 'content',
        private readonly string $varsPrefix = '__view_'
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
        $file = $this->resolveTemplate($template);

        $__viewVarsPrefix = $this->varsPrefix;

        // Create clean scope for template.
        $render = function (string $__file, array &$__data) use ($__viewVarsPrefix) {
            // Extract variables into template scope.
            extract($__data, EXTR_REFS | EXTR_SKIP);

            // Start output buffering.
            ob_start();

            try {
                include $__file;
            } catch (Throwable $e) {
                // Clean output buffer on error.
                ob_end_clean();
                throw $e;
            }

            $__viewVarsPrefixLength = strlen($__viewVarsPrefix);
            $__vars = get_defined_vars();
            foreach ($__vars as $__var => $__val) {
                if (substr($__var, 0, $__viewVarsPrefixLength) === $__viewVarsPrefix) {
                    $__data[$__var] = $__val;
                }
            }

            return ob_get_clean();
        };

        // Merge runtime options with template data.
        $context = array_replace_recursive(
            ['options' => $options],
            $this->createClosures(),
            $data
        );

        // Render template with the context.
        $html = $render($file, $context);

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
        return ['php', 'phtml'];
    }

    /**
     * {@inheritDoc}
     */
    public function getName(): string
    {
        return 'php';
    }

    /**
     * Resolves template path.
     *
     * @param string $template Template name or path.
     * @return string Full template path.
     * @throws TemplateNotFoundException If template cannot be found.
     */
    private function resolveTemplate(string $template): string
    {
        // If absolute path, return as is.
        $realpath = realpath($template);
        if ($realpath) {
            return $template;
        }

        // Add extension if not present.
        if (!str_ends_with($template, '.php') && !str_ends_with($template, '.phtml')) {
            $template .= '.php';
        }

        // Search in configured paths.
        foreach ($this->paths as $path) {
            $file = $path . '/' . $template;
            if (file_exists($file)) {
                return $file;
            }
        }

        throw TemplateNotFoundException::forTemplate($template);
    }

    /**
     * Creates a set of helper closures for use in PHP templates.
     *
     * This method provides a collection of formatting functions that can be
     * used within PHP templates. Each function is implemented as a closure to
     * maintain proper scope and access to the formatter service.
     *
     * Available helpers:
     *
     *   - format_as($value, $format): Formats a value according to the
     *     specified format.
     *   - to_string($value): Converts any value to its string representation.
     *
     * @example Usage in PHP template:
     * ```php
     * <p>Date: <?= $format_as($date, 'short') ?></p>
     * <p>Data: <?= $to_string($complexObject) ?></p>
     * ```
     *
     * @return array<string, Closure> Array of helper functions where:
     *   - key: The closure name available in the template as a variable.
     *   - value: The closure implementing the function.
     */
    private function createClosures(): array
    {
        return [
            'format_as' => fn (mixed $value, string $format): string =>
                $this->formatter->format($value, $format)
            ,
            'to_string' => fn (mixed $value): string =>
                $this->formatter->format($value, 'string')
            ,
        ];
    }
}
