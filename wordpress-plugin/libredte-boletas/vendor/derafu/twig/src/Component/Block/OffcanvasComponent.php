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

#[AsTwigComponent('block-offcanvas')]
class OffcanvasComponent extends AbstractComponent
{
    /**
     * Offcanvas title (optional).
     */
    private ?string $title = null;

    /**
     * Offcanvas content (supports HTML).
     */
    private string $content;

    /**
     * Array of footer buttons.
     *
     * Structure: [
     *   'text' => string,
     *   'type' => string (primary, secondary, etc),
     *   'dismiss' => bool (optional),
     *   'attributes' => string (optional, additional HTML attributes)
     * ]
     */
    private array $buttons = [];

    /**
     * Position of the offcanvas (start, end, top, bottom).
     * Default: start (left side).
     */
    private string $position = 'start';

    /**
     * Show close button in header.
     */
    private bool $showClose = true;

    /**
     * Enable backdrop.
     */
    private bool $withBackdrop = true;

    /**
     * Enable body scrolling while offcanvas is visible.
     */
    private bool $scrollable = false;

    /**
     * Gets the offcanvas title.
     *
     * @return string|null The offcanvas title
     */
    public function getTitle(): ?string
    {
        return $this->title;
    }

    /**
     * Sets the offcanvas title.
     *
     * @param string|null $title The offcanvas title
     * @return static
     */
    public function setTitle(?string $title): static
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Gets the offcanvas content.
     *
     * @return string The offcanvas content
     */
    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * Sets the offcanvas content.
     *
     * @param string $content The offcanvas content
     * @return static
     */
    public function setContent(string $content): static
    {
        $this->content = $content;

        return $this;
    }

    /**
     * Gets the array of footer buttons.
     *
     * @return array The array of footer buttons
     */
    public function getButtons(): array
    {
        return $this->buttons;
    }

    /**
     * Sets the array of footer buttons.
     *
     * @param array $buttons The array of footer buttons
     * @return static
     */
    public function setButtons(array $buttons): static
    {
        $this->buttons = $buttons;

        return $this;
    }

    /**
     * Gets the position of the offcanvas.
     *
     * @return string The position (start, end, top, bottom)
     */
    public function getPosition(): string
    {
        return $this->position;
    }

    /**
     * Sets the position of the offcanvas.
     *
     * @param string $position The position (start, end, top, bottom)
     * @return static
     */
    public function setPosition(string $position): static
    {
        $this->position = $position;

        return $this;
    }

    /**
     * Gets whether the close button is shown.
     *
     * @return bool Whether the close button is shown
     */
    public function getShowClose(): bool
    {
        return $this->showClose;
    }

    /**
     * Sets whether the close button is shown.
     *
     * @param bool $showClose Whether the close button is shown
     * @return static
     */
    public function setShowClose(bool $showClose): static
    {
        $this->showClose = $showClose;

        return $this;
    }

    /**
     * Gets whether the backdrop is enabled.
     *
     * @return bool Whether the backdrop is enabled
     */
    public function getWithBackdrop(): bool
    {
        return $this->withBackdrop;
    }

    /**
     * Sets whether the backdrop is enabled.
     *
     * @param bool $withBackdrop Whether the backdrop is enabled
     * @return static
     */
    public function setWithBackdrop(bool $withBackdrop): static
    {
        $this->withBackdrop = $withBackdrop;

        return $this;
    }

    /**
     * Gets whether body scrolling is enabled.
     *
     * @return bool Whether body scrolling is enabled
     */
    public function getScrollable(): bool
    {
        return $this->scrollable;
    }

    /**
     * Sets whether body scrolling is enabled.
     *
     * @param bool $scrollable Whether body scrolling is enabled
     * @return static
     */
    public function setScrollable(bool $scrollable): static
    {
        $this->scrollable = $scrollable;

        return $this;
    }
}
