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

#[AsTwigComponent('block-grid')]
class GridComponent extends AbstractComponent
{
    /**
     * Array of items to display in the grid.
     *
     * @var array
     */
    private array $items;

    /**
     * Number of columns in the grid layout.
     *
     * @var int|null
     */
    private ?int $cols = null;

    /**
     * Gets the array of items to display in the grid.
     *
     * @return array The array of items.
     */
    public function getItems(): array
    {
        return $this->items;
    }

    /**
     * Sets the array of items to display in the grid.
     *
     * @param array $items The array of items.
     * @return static
     */
    public function setItems(array $items): static
    {
        $this->items = $items;

        return $this;
    }

    /**
     * Gets the number of columns in the grid layout.
     *
     * @return int|null The number of columns.
     */
    public function getCols(): ?int
    {
        return $this->cols;
    }

    /**
     * Sets the number of columns in the grid layout.
     *
     * @param int|null $cols The number of columns.
     * @return static
     */
    public function setCols(?int $cols): static
    {
        $this->cols = $cols;

        return $this;
    }
}
