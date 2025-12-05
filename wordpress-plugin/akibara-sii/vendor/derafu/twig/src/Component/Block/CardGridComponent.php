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

#[AsTwigComponent('block-card-grid')]
class CardGridComponent extends AbstractComponent
{
    /**
     * Array of cards with their configurations.
     *
     * Each card contains:
     * - image: Image URL (optional)
     * - title: Card title (optional)
     * - content: Card content (optional)
     * - button_text: Button text (optional)
     * - button_url: Button URL
     *
     * @var array
     */
    private array $cards = [];

    /**
     * Number of columns in the grid layout (1, 2, 3, 4, or 6).
     *
     * @var int
     */
    private int $cols = 4;

    /**
     * Optional offset for the grid columns.
     *
     * @var int|null
     */
    private ?int $offset = null;

    /**
     * Gets the array of cards.
     *
     * @return array The array of cards with their configurations.
     */
    public function getCards(): array
    {
        return $this->cards;
    }

    /**
     * Sets the array of cards.
     *
     * @param array $cards The array of cards with their configurations.
     * @return static
     */
    public function setCards(array $cards): static
    {
        $this->cards = $cards;

        return $this;
    }

    /**
     * Gets the number of columns in the grid layout.
     *
     * @return int The number of columns (1, 2, 3, 4, or 6).
     */
    public function getCols(): int
    {
        return $this->cols;
    }

    /**
     * Sets the number of columns in the grid layout.
     *
     * @param int $cols The number of columns (1, 2, 3, 4, or 6).
     * @return static
     */
    public function setCols(int $cols): static
    {
        if (!in_array($cols, [1, 2, 3, 4, 6], true)) {
            $this->error('Invalid number of columns. Must be 1, 2, 3, 4 or 6.');
        }

        $this->cols = $cols;

        return $this;
    }

    /**
     * Gets the optional offset for the grid columns.
     *
     * @return int|null The offset value or null if not set.
     */
    public function getOffset(): ?int
    {
        return $this->offset;
    }

    /**
     * Sets the optional offset for the grid columns.
     *
     * @param int|null $offset The offset value or null to unset.
     * @return static
     */
    public function setOffset(?int $offset): static
    {
        $this->offset = $offset;

        return $this;
    }
}
