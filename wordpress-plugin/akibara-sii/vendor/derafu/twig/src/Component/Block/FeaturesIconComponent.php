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

#[AsTwigComponent('block-features-icon')]
class FeaturesIconComponent extends AbstractComponent
{
    /**
     * Array of features with their configurations.
     *
     * Each feature contains:
     * - icon: Font Awesome icon class (e.g. "fa-solid fa-display")
     * - title: Feature title
     * - content: Feature content (supports HTML)
     *
     * @var array
     */
    private array $features = [];

    /**
     * Gets the array of features.
     *
     * @return array The array of features with their configurations.
     */
    public function getFeatures(): array
    {
        return $this->features;
    }

    /**
     * Sets the array of features.
     *
     * @param array $features The array of features with their configurations.
     * @return static
     */
    public function setFeatures(array $features): static
    {
        $this->features = $features;

        return $this;
    }
}
