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

#[AsTwigComponent('block-text-image')]
class TextImageComponent extends AbstractComponent
{
    /**
     * Title of the text section.
     *
     * @var string
     */
    private string $title;

    /**
     * Main content text (supports paragraphs).
     *
     * @var string
     */
    private string $content;

    /**
     * Array of button configurations.
     * Each button contains: {text, url}
     *
     * @var array
     */
    private array $buttons = [];

    /**
     * Number of columns for text section (Bootstrap grid).
     *
     * @var int
     */
    private int $cols = 7;

    /**
     * URL of the featured image.
     *
     * @var string
     */
    private string $image;

    /**
     * Position of the image (right, left).
     *
     * @var string
     */
    private string $imagePosition = 'right';

    /**
     * Optional flag to indicate rendering inside tabs.
     *
     * @var bool
     */
    private bool $insideTabs = false;

    /**
     * Gets the title of the text section.
     *
     * @return string The title.
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * Sets the title of the text section.
     *
     * @param string $title The title.
     * @return static
     */
    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Gets the main content text.
     *
     * @return string The content.
     */
    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * Sets the main content text.
     *
     * @param string $content The content.
     * @return static
     */
    public function setContent(string $content): static
    {
        $this->content = $content;

        return $this;
    }

    /**
     * Gets the array of button configurations.
     *
     * @return array<int,array> The array of buttons.
     */
    public function getButtons(): array
    {
        return $this->buttons;
    }

    /**
     * Sets the array of button configurations.
     *
     * @param array<int,array> $buttons The array of buttons.
     * @return static
     */
    public function setButtons(array $buttons): static
    {
        $this->buttons = $buttons;

        return $this;
    }

    /**
     * Gets the number of columns for text section.
     *
     * @return int The number of columns.
     */
    public function getCols(): int
    {
        return $this->cols;
    }

    /**
     * Sets the number of columns for text section.
     *
     * @param int $cols The number of columns.
     * @return static
     */
    public function setCols(int $cols): static
    {
        $this->cols = $cols;

        return $this;
    }

    /**
     * Gets the URL of the featured image.
     *
     * @return string The image URL.
     */
    public function getImage(): string
    {
        return $this->image;
    }

    /**
     * Sets the URL of the featured image.
     *
     * @param string $image The image URL.
     * @return static
     */
    public function setImage(string $image): static
    {
        $this->image = $image;

        return $this;
    }

    /**
     * Gets the position of the image.
     *
     * @return string The image position.
     */
    public function getImagePosition(): string
    {
        return $this->imagePosition;
    }

    /**
     * Sets the position of the image.
     *
     * @param string $imagePosition The image position.
     * @return static
     */
    public function setImagePosition(string $imagePosition): static
    {
        $this->imagePosition = $imagePosition;

        return $this;
    }

    /**
     * Gets the flag indicating if component is rendered inside tabs.
     *
     * @return bool Whether component is inside tabs.
     */
    public function getInsideTabs(): bool
    {
        return $this->insideTabs;
    }

    /**
     * Sets the flag indicating if component is rendered inside tabs.
     *
     * @param bool $insideTabs Whether component is inside tabs.
     * @return static
     */
    public function setInsideTabs(bool $insideTabs): static
    {
        $this->insideTabs = $insideTabs;

        return $this;
    }
}
