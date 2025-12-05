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

#[AsTwigComponent('block-features-list')]
class FeaturesListComponent extends AbstractComponent
{
    /**
     * Array of features for the left side.
     *
     * Each feature contains:
     * - icon: Font Awesome icon class (e.g. "fa-solid fa-display")
     * - title: Feature title
     * - content: Feature content (supports HTML)
     *
     * @var array
     */
    private array $features_left = [];

    /**
     * Array of features for the right side.
     *
     * Each feature contains:
     * - icon: Font Awesome icon class (e.g. "fa-solid fa-display")
     * - title: Feature title
     * - content: Feature content (supports HTML)
     *
     * @var array
     */
    private array $features_right = [];

    /**
     * Gets the array of features for the left side.
     *
     * @return array The array of features with their configurations.
     */
    public function getFeaturesLeft(): array
    {
        return $this->features_left;
    }

    /**
     * Sets the array of features for the left side.
     *
     * @param array $features_left The array of features with their configurations.
     * @return static
     */
    public function setFeaturesLeft(array $features_left): static
    {
        $this->features_left = $features_left;

        return $this;
    }

    /**
     * Gets the array of features for the right side.
     *
     * @return array The array of features with their configurations.
     */
    public function getFeaturesRight(): array
    {
        return $this->features_right;
    }

    /**
     * Sets the array of features for the right side.
     *
     * @param array $features_right The array of features with their configurations.
     * @return static
     */
    public function setFeaturesRight(array $features_right): static
    {
        $this->features_right = $features_right;

        return $this;
    }
}
