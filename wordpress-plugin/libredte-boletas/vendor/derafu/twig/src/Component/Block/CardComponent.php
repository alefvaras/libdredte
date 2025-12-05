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

#[AsTwigComponent('block-card')]
class CardComponent extends AbstractComponent
{
    /**
     * Optional card image URL.
     */
    private ?string $image = null;

    /**
     * Optional card title.
     */
    private ?string $title = null;

    /**
     * Optional card content.
     */
    private ?string $content = null;

    /**
     * Optional button text.
     */
    private ?string $buttonText = null;

    /**
     * Optional button URL (required if buttonText is present).
     */
    private ?string $buttonUrl = null;

    /**
     * Gets the card image URL.
     *
     * @return string|null The card image URL.
     */
    public function getImage(): ?string
    {
        return $this->image;
    }

    /**
     * Sets the card image URL.
     *
     * @param string|null $image The card image URL.
     * @return static
     */
    public function setImage(?string $image): static
    {
        $this->image = $image;

        return $this;
    }

    /**
     * Gets the card title.
     *
     * @return string|null The card title.
     */
    public function getTitle(): ?string
    {
        return $this->title;
    }

    /**
     * Sets the card title.
     *
     * @param string|null $title The card title.
     * @return static
     */
    public function setTitle(?string $title): static
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Gets the card content.
     *
     * @return string|null The card content.
     */
    public function getContent(): ?string
    {
        return $this->content;
    }

    /**
     * Sets the card content.
     *
     * @param string|null $content The card content.
     * @return static
     */
    public function setContent(?string $content): static
    {
        $this->content = $content;

        return $this;
    }

    /**
     * Gets the button text.
     *
     * @return string|null The button text.
     */
    public function getButtonText(): ?string
    {
        return $this->buttonText;
    }

    /**
     * Sets the button text.
     *
     * @param string|null $buttonText The button text.
     * @return static
     */
    public function setButtonText(?string $buttonText): static
    {
        $this->buttonText = $buttonText;

        return $this;
    }

    /**
     * Gets the button URL.
     *
     * @return string|null The button URL.
     */
    public function getButtonUrl(): ?string
    {
        return $this->buttonUrl;
    }

    /**
     * Sets the button URL.
     *
     * @param string|null $buttonUrl The button URL.
     * @return static
     */
    public function setButtonUrl(?string $buttonUrl): static
    {
        $this->buttonUrl = $buttonUrl;

        return $this;
    }
}
