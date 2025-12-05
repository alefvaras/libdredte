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
 * Toast Component for displaying notification messages.
 *
 * This component creates Bootstrap-based toast notifications that can be
 * configured with different types, titles, content and icons.
 */
#[AsTwigComponent('block-toast')]
class ToastComponent extends AbstractComponent
{
    /**
     * Toast type (primary, secondary, tertiary).
     *
     * Default: 'primary'
     *
     * @var string
     */
    private string $type = 'primary';

    /**
     * Toast title.
     *
     * @var string|null
     */
    private ?string $title = null;

    /**
     * Toast content/message.
     *
     * @var string|null
     */
    private ?string $content = null;

    /**
     * Time display (e.g., "2 mins ago").
     *
     * @var string|null
     */
    private ?string $time = null;

    /**
     * Optional Font Awesome icon class
     */
    private ?string $icon = null;

    /**
     * Gets the toast type.
     *
     * @return string The toast type.
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Sets the toast type.
     *
     * @param string $type The toast type.
     * @return static
     */
    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Gets the toast title.
     *
     * @return string|null The toast title.
     */
    public function getTitle(): ?string
    {
        return $this->title;
    }

    /**
     * Sets the toast title.
     *
     * @param string|null $title The toast title.
     * @return static
     */
    public function setTitle(?string $title): static
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Gets the toast content.
     *
     * @return string|null The toast content.
     */
    public function getContent(): ?string
    {
        return $this->content;
    }

    /**
     * Sets the toast content.
     *
     * @param string|null $content The toast content.
     * @return static
     */
    public function setContent(?string $content): static
    {
        $this->content = $content;

        return $this;
    }

    /**
     * Gets the time display.
     *
     * @return string|null The time display.
     */
    public function getTime(): ?string
    {
        return $this->time;
    }

    /**
     * Sets the time display.
     *
     * @param string|null $time The time display.
     * @return static
     */
    public function setTime(?string $time): static
    {
        $this->time = $time;

        return $this;
    }

    /**
     * Gets the icon class.
     *
     * @return string|null The icon class.
     */
    public function getIcon(): ?string
    {
        if (!isset($this->icon)) {
            $this->icon = $this->getDefaultIcon($this->type);
        }

        return $this->icon;
    }

    /**
     * Sets the icon class.
     *
     * @param string|null $icon The icon class.
     * @return static
     */
    public function setIcon(?string $icon): static
    {
        $this->icon = $icon;

        return $this;
    }
}
