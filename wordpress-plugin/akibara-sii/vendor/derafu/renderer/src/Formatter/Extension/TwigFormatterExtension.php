<?php

declare(strict_types=1);

/**
 * Derafu: Renderer - Unified Template Rendering Made Simple For PHP.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Renderer\Formatter\Extension;

use Derafu\Renderer\Contract\FormatterInterface;
use Twig\Extension\AbstractExtension;
use Twig\Markup;
use Twig\TwigFilter;

/**
 * Twig extension for using the formatter in templates.
 */
class TwigFormatterExtension extends AbstractExtension
{
    /**
     * Character encoding for renderings returned by the extension's functions.
     *
     * @var string
     */
    protected $charset = 'UTF-8';

    /**
     * Extension constructor.
     *
     * Dependency injected to format values.
     *
     * @param FormatterInterface $formatter
     */
    public function __construct(
        private readonly FormatterInterface $formatter
    ) {
    }

    /**
     * Returns the filters available in this extension.
     *
     * @return array
     */
    public function getFilters(): array
    {
        return [
            new TwigFilter('format_as', [$this, 'format_as']),
            new TwigFilter('to_string', [$this, 'to_string']),
        ];
    }

    /**
     * Formats a value according to an ID that tells it how to format.
     *
     * @param mixed $value
     * @param string $format
     * @return Markup
     */
    public function format_as(mixed $value, string $format): Markup
    {
        $html = $this->formatter->format($value, $format);

        return new Markup($html, $this->charset);
    }

    /**
     * Converts a value to a string.
     *
     * @param mixed $value
     * @return Markup
     */
    public function to_string(mixed $value): Markup
    {
        $html = $this->formatter->format($value, 'string');

        return new Markup($html, $this->charset);
    }
}
