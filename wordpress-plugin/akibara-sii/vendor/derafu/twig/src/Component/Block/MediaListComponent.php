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

/**
 * Component to display a list of media items.
 */
#[AsTwigComponent('block-media-list')]
class MediaListComponent extends AbstractComponent
{
    /**
     * List of items to display.
     *
     * @var array<array{image: string, title: string, content: string}>
     */
    private array $items = [];

    /**
     * Whether to round the corners of the images.
     *
     * @var bool
     */
    private bool $rounded = false;

    /**
     * The level of the title.
     *
     * @var int
     */
    private int $titleLevel = 3;

    /**
     * Gets the list of items to display.
     *
     * @return array<array{image: string, title: string, content: string}>
     */
    public function getItems(): array
    {
        return $this->items;
    }

    /**
     * Sets the list of items to display.
     *
     * @param array<array{image: string, title: string, content: string}> $items
     * @return static
     */
    public function setItems(array $items): static
    {
        $this->items = $items;

        return $this;
    }

    /**
     * Gets the rounded attribute.
     *
     * @return boolean
     */
    public function getRounded(): bool
    {
        return $this->rounded;
    }

    /**
     * Sets the rounded attribute.
     *
     * @param boolean $rounded
     * @return static
     */
    public function setRounded(bool $rounded): static
    {
        $this->rounded = $rounded;

        return $this;
    }

    /**
     * Gets the title level.
     *
     * @return int
     */
    public function getTitleLevel(): int
    {
        return $this->titleLevel;
    }

    /**
     * Sets the title level.
     *
     * @param int $titleLevel
     * @return static
     */
    public function setTitleLevel(int $titleLevel): static
    {
        $this->titleLevel = $titleLevel;

        return $this;
    }
}
