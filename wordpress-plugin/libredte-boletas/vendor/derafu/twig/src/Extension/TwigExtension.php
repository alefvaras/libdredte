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

use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * Twig extension to enable Twig rendering within templates.
 *
 * This extension provides a `twig` filter to convert Twig content in a string
 * into HTML using Twig.
 */
class TwigExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('twig', [$this, 'renderTwig'], [
                'is_safe' => ['html'],
                'needs_environment' => true,
                'needs_context' => true,
            ]),
        ];
    }

    public function renderTwig(Environment $twig, array $context, string $templateString): string
    {
        $template = $twig->createTemplate($templateString);

        return $template->render($context);
    }
}
