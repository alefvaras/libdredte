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

#[AsTwigComponent('block-boxes')]
class BoxesComponent extends AbstractComponent
{
    /**
     * Array of boxes with their configurations.
     *
     * Each box contains:
     *
     *   - icon: Icon name.
     *   - title: Box title.
     *   - content: Box content.
     *   - button_text: Button text.
     *   - button_url: Button URL.
     *
     * @var array<int,array<string,string|null>>
     */
    private array $boxes = [];

    /**
     * Number of columns in the layout (2 or 3).
     *
     * @var int
     */
    private int $cols = 2;

    /**
     * Alignment of the boxes (left, center, right).
     *
     * @var string
     */
    private string $align = 'center';

    /**
     * Gets the boxes.
     *
     * @return array<int,array<string,string|null>>
     */
    public function getBoxes(): array
    {
        return $this->boxes;
    }

    /**
     * Sets the boxes.
     *
     * @param array<int,array<string,string|null>> $boxes The boxes.
     * @return static
     */
    public function setBoxes(array $boxes): static
    {
        $this->boxes = $boxes;

        return $this;
    }

    /**
     * Gets the number of columns.
     *
     * @return int
     */
    public function getCols(): int
    {
        return $this->cols;
    }

    /**
     * Sets the number of columns.
     *
     * @param int $cols The number of columns.
     * @return static
     */
    public function setCols(int $cols): static
    {
        if (!in_array($cols, [1, 2, 3, 4, 6])) {
            $this->error('The number of columns must be 1, 2, 3, 4 or 6.');
        }

        $this->cols = $cols;

        return $this;
    }

    /**
     * Gets the alignment of the boxes.
     *
     * @return string
     */
    public function getAlign(): string
    {
        return $this->align;
    }

    /**
     * Sets the alignment of the boxes.
     *
     * @param string $align The alignment of the boxes.
     * @return static
     */
    public function setAlign(string $align): static
    {
        $this->align = $align;

        return $this;
    }
}
