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
 * Modal Component for displaying popup dialogs in the application.
 *
 * This component creates a Bootstrap-based modal dialog that can be configured
 * with different content, buttons, and styles.
 */
#[AsTwigComponent('block-modal')]
class ModalComponent extends AbstractComponent
{
    /**
     * Modal title (optional).
     */
    private ?string $title = null;

    /**
     * Modal content (supports HTML).
     */
    private string $content;

    /**
     * Array of footer buttons.
     * Structure: [
     *   'text' => string,
     *   'type' => string (primary, secondary, etc),
     *   'dismiss' => bool (optional),
     *   'attributes' => string (optional, additional HTML attributes)
     * ]
     */
    private array $buttons = [];

    /**
     * Modal size (sm, lg, xl, fullscreen).
     */
    private ?string $size = null;

    /**
     * Show close button in header.
     */
    private bool $showClose = true;

    /**
     * Static backdrop (modal won't close on backdrop click).
     */
    private bool $withStaticBackdrop = false;

    /**
     * Modal is vertically centered.
     */
    private bool $centered = false;

    /**
     * Scrollable content.
     */
    private bool $scrollable = false;

    /**
     * Gets the modal title.
     *
     * @return string|null The modal title.
     */
    public function getTitle(): ?string
    {
        return $this->title;
    }

    /**
     * Sets the modal title.
     *
     * @param string|null $title The modal title.
     * @return static
     */
    public function setTitle(?string $title): static
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Gets the modal content.
     *
     * @return string The modal content.
     */
    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * Sets the modal content.
     *
     * @param string $content The modal content.
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
     * @return array The array of footer buttons.
     */
    public function getButtons(): array
    {
        return $this->buttons;
    }

    /**
     * Sets the array of footer buttons.
     *
     * @param array $buttons The array of footer buttons.
     * @return static
     */
    public function setButtons(array $buttons): static
    {
        $this->buttons = $buttons;

        return $this;
    }

    /**
     * Gets the modal size.
     *
     * @return string|null The modal size.
     */
    public function getSize(): ?string
    {
        return $this->size;
    }

    /**
     * Sets the modal size.
     *
     * @param string|null $size The modal size.
     * @return static
     */
    public function setSize(?string $size): static
    {
        $this->size = $size;

        return $this;
    }

    /**
     * Gets whether to show the close button in header.
     *
     * @return bool Whether to show the close button.
     */
    public function getShowClose(): bool
    {
        return $this->showClose;
    }

    /**
     * Sets whether to show the close button in header.
     *
     * @param bool $showClose Whether to show the close button.
     * @return static
     */
    public function setShowClose(bool $showClose): static
    {
        $this->showClose = $showClose;

        return $this;
    }

    /**
     * Gets whether the modal has a static backdrop.
     *
     * @return bool Whether the modal has a static backdrop.
     */
    public function getWithStaticBackdrop(): bool
    {
        return $this->withStaticBackdrop;
    }

    /**
     * Sets whether the modal has a static backdrop.
     *
     * @param bool $withStaticBackdrop Whether the modal has a static backdrop.
     * @return static
     */
    public function setWithStaticBackdrop(bool $withStaticBackdrop): static
    {
        $this->withStaticBackdrop = $withStaticBackdrop;

        return $this;
    }

    /**
     * Gets whether the modal is vertically centered.
     *
     * @return bool Whether the modal is vertically centered.
     */
    public function getCentered(): bool
    {
        return $this->centered;
    }

    /**
     * Sets whether the modal is vertically centered.
     *
     * @param bool $centered Whether the modal is vertically centered.
     * @return static
     */
    public function setCentered(bool $centered): static
    {
        $this->centered = $centered;

        return $this;
    }

    /**
     * Gets whether the modal content is scrollable.
     *
     * @return bool Whether the modal content is scrollable.
     */
    public function getScrollable(): bool
    {
        return $this->scrollable;
    }

    /**
     * Sets whether the modal content is scrollable.
     *
     * @param bool $scrollable Whether the modal content is scrollable.
     * @return static
     */
    public function setScrollable(bool $scrollable): static
    {
        $this->scrollable = $scrollable;

        return $this;
    }
}
