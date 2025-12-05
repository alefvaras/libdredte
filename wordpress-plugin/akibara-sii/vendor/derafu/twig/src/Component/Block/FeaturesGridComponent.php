<?php

declare(strict_types=1);

/**
 * Derafu: Twig - UI Component and Extension Library.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Twig\Component\Block;

use Derafu\Twig\Abstract\AbstractComponent;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent('block-features-grid')]
class FeaturesGridComponent extends AbstractComponent
{
    /**
     * Array of content blocks with their configurations.
     *
     * Each block contains:
     * - title: Block title
     * - subtitle: Block subtitle
     * - features: Array of features:
     *   - icon: Font Awesome icon class
     *   - title: Feature title
     *   - content: Feature content (supports HTML)
     *
     * @var array
     */
    private array $blocks = [];

    /**
     * Gets the array of blocks.
     *
     * @return array The array of blocks with their configurations.
     */
    public function getBlocks(): array
    {
        return $this->blocks;
    }

    /**
     * Sets the array of blocks.
     *
     * @param array $blocks The array of blocks with their configurations.
     * @return static
     */
    public function setBlocks(array $blocks): static
    {
        $this->blocks = $blocks;

        return $this;
    }
}
