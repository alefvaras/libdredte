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

#[AsTwigComponent('block-features-table')]
class FeaturesTableComponent extends AbstractComponent
{
    /**
     * Title of the features table
     *
     * @var string
     */
    private string $title = 'Features';

    /**
     * Features array containing the rows of the table
     *
     * Example structure:
     * [
     *     'icon' => 'fa-solid fa-folder',
     *     'name' => 'Feature Name',
     *     'content' => 'Optional content text',
     *     'values' => [
     *         'Plan A' => true,                   // For boolean (check/cross).
     *         'Plan B' => '1,400',                // For text/numeric.
     *         'Plan C' => '<strong>...</strong>'  // For HTML content.
     *     ]
     * ]
     *
     * @var array
     */
    private array $features = [];

    /**
     * Gets the title of the features table.
     *
     * @return string The title of the features table.
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * Sets the title of the features table.
     *
     * @param string $title The title of the features table.
     * @return static
     */
    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

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
