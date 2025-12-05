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
 * Title Component for displaying customizable headings.
 *
 * This component creates headings with optional subtitle and styling options
 * like alignment and bottom border.
 */
#[AsTwigComponent('block-title')]
class TitleComponent extends AbstractComponent
{
    /**
     * The title text (supports HTML).
     *
     * @var string
     */
    private string $title;

    /**
     * Optional subtitle text (supports HTML).
     *
     * @var string|null
     */
    private ?string $subtitle = null;

    /**
     * Heading level (1-6 => h1-h6).
     *
     * @var int
     */
    private int $level = 2;

    /**
     * Text alignment (left, center, right).
     *
     * @var string
     */
    private string $align = 'center';

    /**
     * Show bottom border.
     *
     * @var bool
     */
    private bool $border = false;

    /**
     * Gets the title text.
     *
     * @return string The title text.
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * Sets the title text.
     *
     * @param string $title The title text.
     * @return static
     */
    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Gets the subtitle text.
     *
     * @return string|null The subtitle text.
     */
    public function getSubtitle(): ?string
    {
        return $this->subtitle;
    }

    /**
     * Sets the subtitle text.
     *
     * @param string|null $subtitle The subtitle text.
     * @return static
     */
    public function setSubtitle(?string $subtitle): static
    {
        $this->subtitle = $subtitle;

        return $this;
    }

    /**
     * Gets the heading level.
     *
     * @return int The heading level.
     */
    public function getLevel(): int
    {
        return $this->level;
    }

    /**
     * Sets the heading level.
     *
     * @param int $level The heading level.
     * @return static
     */
    public function setLevel(int $level): static
    {
        $this->level = $level;

        return $this;
    }

    /**
     * Gets the text alignment.
     *
     * @return string The text alignment.
     */
    public function getAlign(): string
    {
        return $this->align;
    }

    /**
     * Sets the text alignment.
     *
     * @param string $align The text alignment.
     * @return static
     */
    public function setAlign(string $align): static
    {
        $this->align = $align;

        return $this;
    }

    /**
     * Gets whether bottom border is shown.
     *
     * @return bool Whether bottom border is shown.
     */
    public function getBorder(): bool
    {
        return $this->border;
    }

    /**
     * Sets whether bottom border is shown.
     *
     * @param bool $border Whether to show bottom border.
     * @return static
     */
    public function setBorder(bool $border): static
    {
        $this->border = $border;

        return $this;
    }
}
