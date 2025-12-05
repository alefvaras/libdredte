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

#[AsTwigComponent('block-alert')]
class AlertComponent extends AbstractComponent
{
    /**
     * Alert type (e.g., 'primary', 'secondary', 'success').
     *
     * Default: 'primary'
     *
     * @var string
     */
    private string $type = 'primary';

    /**
     * Alert content (supports HTML).
     *
     * @var string
     */
    private string $content;

    /**
     * Icon class (e.g., "fa-solid fa-house" from Font Awesome).
     *
     * @var string
     */
    private string $icon;

    /**
     * Whether the alert is dismissible.
     *
     * @var bool
     */
    private bool $dismissible = false;

    /**
     * Gets the alert type.
     *
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Sets the alert type.
     *
     * @param string $type The alert type.
     * @return static
     */
    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Gets the alert content.
     *
     * @return string
     */
    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * Sets the alert content.
     *
     * @param string $content The alert content.
     * @return static
     */
    public function setContent(string $content): static
    {
        $this->content = $content;

        return $this;
    }

    /**
     * Gets the icon class.
     *
     * @return string
     */
    public function getIcon(): string
    {
        if (!isset($this->icon)) {
            $this->icon = $this->getDefaultIcon($this->getType());
        }

        return $this->icon;
    }

    /**
     * Sets the icon class.
     *
     * @return static
     */
    public function setIcon(string $icon): static
    {
        $this->icon = $icon;

        return $this;
    }

    /**
     * Gets the dismissible flag.
     *
     * @return bool
     */
    public function getDismissible(): bool
    {
        return $this->dismissible;
    }

    /**
     * Sets the dismissible flag.
     *
     * @param bool $dismissible The dismissible flag.
     * @return static
     */
    public function setDismissible(bool $dismissible): static
    {
        $this->dismissible = $dismissible;

        return $this;
    }
}
