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

#[AsTwigComponent('block-image-grid')]
class ImageGridComponent extends AbstractComponent
{
    /**
     * Array of images with their configurations.
     *
     * Each image contains:
     * - image: Image URL
     * - url: Link URL (optional)
     * - tooltip: Hover text (optional)
     *
     * @var array
     */
    private array $images = [];

    /**
     * Number of columns in the grid layout (3, 4 or 6).
     *
     * @var int
     */
    private int $cols = 6;

    /**
     * Gets the array of images.
     *
     * @return array The array of images with their configurations.
     */
    public function getImages(): array
    {
        return $this->images;
    }

    /**
     * Sets the array of images.
     *
     * @param array $images The array of images with their configurations.
     * @return static
     */
    public function setImages(array $images): static
    {
        $this->images = $images;

        return $this;
    }

    /**
     * Gets the number of columns in the grid layout.
     *
     * @return int The number of columns in the grid layout.
     */
    public function getCols(): int
    {
        return $this->cols;
    }

    /**
     * Sets the number of columns in the grid layout.
     *
     * @param int $cols The number of columns in the grid layout.
     * @return static
     */
    public function setCols(int $cols): static
    {
        $this->cols = $cols;

        return $this;
    }
}
