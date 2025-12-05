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

#[AsTwigComponent('block-image')]
class ImageComponent extends AbstractComponent
{
    /**
     * Title of the image section.
     *
     * @var string
     */
    private string $title;

    /**
     * URL of the image.
     *
     * @var string
     */
    private string $image;

    /**
     * Optional content text for the image.
     *
     * @var string|null
     */
    private ?string $content = null;

    /**
     * Array of buttons configurations.
     *
     * Each button contains: {text, url}
     *
     * @var array
     */
    private array $buttons = [];

    /**
     * Content alignment (center, left, right).
     *
     * @var string
     */
    private string $align = 'center';

    /**
     * Size of the image section (small, medium, large).
     *
     * @var string
     */
    private string $size = 'medium';

    /**
     * Gets the title of the image section.
     *
     * @return string The title.
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * Sets the title of the image section.
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
     * Gets the URL of the image.
     *
     * @return string The image URL.
     */
    public function getImage(): string
    {
        return $this->image;
    }

    /**
     * Sets the URL of the image.
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
     * Gets the optional content text.
     *
     * @return string|null The content text.
     */
    public function getContent(): ?string
    {
        return $this->content;
    }

    /**
     * Sets the optional content text.
     *
     * @param string|null $content The content text.
     * @return static
     */
    public function setContent(?string $content): static
    {
        $this->content = $content;

        return $this;
    }

    /**
     * Gets the array of buttons configurations.
     *
     * @return array The buttons configurations.
     */
    public function getButtons(): array
    {
        return $this->buttons;
    }

    /**
     * Sets the array of buttons configurations.
     *
     * @param array $buttons The buttons configurations.
     * @return static
     */
    public function setButtons(array $buttons): static
    {
        $this->buttons = $buttons;

        return $this;
    }

    /**
     * Gets the content alignment.
     *
     * @return string The alignment (center, left, right).
     */
    public function getAlign(): string
    {
        return $this->align;
    }

    /**
     * Sets the content alignment.
     *
     * @param string $align The alignment (center, left, right).
     * @return static
     */
    public function setAlign(string $align): static
    {
        $this->align = $align;

        return $this;
    }

    /**
     * Gets the size of the image section.
     *
     * @return string The size (small, medium, large).
     */
    public function getSize(): string
    {
        return $this->size;
    }

    /**
     * Sets the size of the image section.
     *
     * @param string $size The size (small, medium, large).
     * @return static
     */
    public function setSize(string $size): static
    {
        $this->size = $size;

        return $this;
    }
}
