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

#[AsTwigComponent('block-hero')]
class HeroComponent extends AbstractComponent
{
    /**
     * Main title of the hero section.
     *
     * @var string
     */
    private string $title;

    /**
     * Subtitle text of the hero section.
     *
     * @var string|null
     */
    private ?string $subtitle = null;

    /**
     * Array of buttons configurations.
     *
     * @var array
     */
    private array $buttons = [];

    /**
     * Background color for content wrapper.
     * Example: 'rgba(255, 255, 255, 0.9)' or '#ffffff'.
     *
     * @var string|null
     */
    private ?string $contentBackground = null;

    /**
     * Size of the hero section (mini, small, medium, large, full).
     *
     * @var string
     */
    private string $size = 'medium';

    /**
     * Content alignment (left, center, right).
     *
     * @var string
     */
    private string $align = 'center';

    /**
     * Background image URL or color.
     *
     * @var string|null
     */
    private ?string $background = null;

    /**
     * Whether the hero section should be full width.
     *
     * @var bool
     */
    private bool $fullWidth = false;

    /**
     * Gets the main title of the hero section.
     *
     * @return string The main title
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * Sets the main title of the hero section.
     *
     * @param string $title The main title
     * @return static
     */
    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Gets the subtitle text of the hero section.
     *
     * @return string|null The subtitle text
     */
    public function getSubtitle(): ?string
    {
        return $this->subtitle;
    }

    /**
     * Sets the subtitle text of the hero section.
     *
     * @param string|null $subtitle The subtitle text
     * @return static
     */
    public function setSubtitle(?string $subtitle): static
    {
        $this->subtitle = $subtitle;

        return $this;
    }

    /**
     * Gets the array of buttons configurations.
     *
     * @return array The buttons configurations
     */
    public function getButtons(): array
    {
        return $this->buttons;
    }

    /**
     * Sets the array of buttons configurations.
     *
     * @param array $buttons The buttons configurations
     * @return static
     */
    public function setButtons(array $buttons): static
    {
        $this->buttons = $buttons;

        return $this;
    }

    /**
     * Gets the background color for content wrapper.
     *
     * @return string|null The background color
     */
    public function getContentBackground(): ?string
    {
        return $this->contentBackground;
    }

    /**
     * Sets the background color for content wrapper.
     *
     * @param string|null $contentBackground The background color
     * @return static
     */
    public function setContentBackground(?string $contentBackground): static
    {
        $this->contentBackground = $contentBackground;

        return $this;
    }

    /**
     * Gets the size of the hero section.
     *
     * @return string The size (mini, small, medium, large, full)
     */
    public function getSize(): string
    {
        return $this->size;
    }

    /**
     * Sets the size of the hero section.
     *
     * @param string $size The size (mini, small, medium, large, full)
     * @return static
     */
    public function setSize(string $size): static
    {
        $this->size = $size;

        return $this;
    }

    /**
     * Gets the content alignment.
     *
     * @return string The alignment (left, center, right)
     */
    public function getAlign(): string
    {
        return $this->align;
    }

    /**
     * Sets the content alignment.
     *
     * @param string $align The alignment (left, center, right)
     * @return static
     */
    public function setAlign(string $align): static
    {
        $this->align = $align;

        return $this;
    }

    /**
     * Gets the background image URL or color.
     *
     * @return string|null The background image URL or color
     */
    public function getBackground(): ?string
    {
        return $this->background;
    }

    /**
     * Sets the background image URL or color.
     *
     * @param string|null $background The background image URL or color
     * @return static
     */
    public function setBackground(?string $background): static
    {
        $this->background = $background;

        return $this;
    }

    /**
     * Gets whether the hero section should be full width.
     *
     * @return bool Whether the hero section should be full width
     */
    public function getFullWidth(): bool
    {
        return $this->fullWidth;
    }

    /**
     * Sets whether the hero section should be full width.
     *
     * @param bool $fullWidth Whether the hero section should be full width
     * @return static
     */
    public function setFullWidth(bool $fullWidth): static
    {
        $this->fullWidth = $fullWidth;

        return $this;
    }
}
