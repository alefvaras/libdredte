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
 * Video Component for displaying embedded videos with additional content.
 *
 * This component creates customizable video blocks that can include
 * title, content, action buttons and various display options.
 */
#[AsTwigComponent('block-video')]
class VideoComponent extends AbstractComponent
{
    /**
     * Video title.
     *
     * @var string
     */
    private string $title;

    /**
     * Video URL.
     *
     * @var string
     */
    private string $video;

    /**
     * Video content.
     *
     * @var string
     */
    private string $content;

    /**
     * Array of action buttons.
     *
     * @var array
     */
    private array $buttons = [];

    /**
     * Video size.
     *
     * @var string
     */
    private string $size;

    /**
     * Video alignment.
     *
     * @var string
     */
    private string $align;

    /**
     * Video aspect ratio.
     *
     * @var string
     */
    private string $aspectRatio;

    /**
     * Gets the video title.
     *
     * @return string|null The video title.
     */
    public function getTitle(): ?string
    {
        return $this->title ?? null;
    }

    /**
     * Sets the video title.
     *
     * @param string $title The video title.
     * @return static
     */
    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Gets the video URL.
     *
     * @return string The video URL.
     */
    public function getVideo(): string
    {
        return $this->video;
    }

    /**
     * Sets the video URL.
     *
     * @param string $video The video URL.
     * @return static
     */
    public function setVideo(string $video): static
    {
        $this->video = $video;

        return $this;
    }

    /**
     * Gets the video content.
     *
     * @return string|null The video content.
     */
    public function getContent(): ?string
    {
        return $this->content ?? null;
    }

    /**
     * Sets the video content.
     *
     * @param string $content The video content.
     * @return static
     */
    public function setContent(string $content): static
    {
        $this->content = $content;

        return $this;
    }

    /**
     * Gets the array of action buttons.
     *
     * @return array The array of action buttons.
     */
    public function getButtons(): array
    {
        return $this->buttons;
    }

    /**
     * Sets the array of action buttons.
     *
     * @param array $buttons The array of action buttons.
     * @return static
     */
    public function setButtons(array $buttons): static
    {
        $this->buttons = $buttons;

        return $this;
    }

    /**
     * Gets the video size.
     *
     * @return string The video size.
     */
    public function getSize(): string
    {
        return $this->size;
    }

    /**
     * Sets the video size.
     *
     * @param string $size The video size.
     * @return static
     */
    public function setSize(string $size): static
    {
        $this->size = $size;

        return $this;
    }

    /**
     * Gets the video alignment.
     *
     * @return string The video alignment.
     */
    public function getAlign(): string
    {
        return $this->align;
    }

    /**
     * Sets the video alignment.
     *
     * @param string $align The video alignment.
     * @return static
     */
    public function setAlign(string $align): static
    {
        $this->align = $align;

        return $this;
    }

    /**
     * Gets the video aspect ratio.
     *
     * @return string The video aspect ratio.
     */
    public function getAspectRatio(): string
    {
        return $this->aspectRatio;
    }

    /**
     * Sets the video aspect ratio.
     *
     * @param string $aspectRatio The video aspect ratio.
     * @return static
     */
    public function setAspectRatio(string $aspectRatio): static
    {
        $this->aspectRatio = $aspectRatio;

        return $this;
    }
}
