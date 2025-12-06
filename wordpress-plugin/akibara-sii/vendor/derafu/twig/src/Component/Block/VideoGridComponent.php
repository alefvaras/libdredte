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
 * Video Grid Component for displaying multiple videos in a grid layout.
 *
 * This component creates a responsive grid of video blocks with optional
 * titles, contents, and action buttons.
 */
#[AsTwigComponent('block-video-grid')]
class VideoGridComponent extends AbstractComponent
{
    /**
     * Array of videos with their configurations.
     *
     * Each video contains:
     *
     *   - title: Video title (optional).
     *   - video: YouTube URL (required).
     *   - content: Video content (optional, supports HTML).
     *   - buttons: Array of action buttons (optional).
     *      - text: Button text.
     *      - url: Button URL.
     *   - card: Display as card (optional, default: true).
     *
     * @var array
     */
    private array $videos = [];

    /**
     * Number of columns in the grid layout.
     *
     * @var int
     */
    private int $cols = 2;

    /**
     * Gets the array of videos.
     *
     * @return array<int,array> The array of videos.
     */
    public function getVideos(): array
    {
        return $this->videos;
    }

    /**
     * Sets the array of videos.
     *
     * @param array<int,array> $videos The array of videos.
     * @return static
     */
    public function setVideos(array $videos): static
    {
        $this->videos = $videos;

        return $this;
    }

    /**
     * Gets the number of columns in the grid layout.
     *
     * @return int The number of columns in the grid layout.
     */
    public function getCols(): int
    {
        return $this->cols;
    }

    /**
     * Sets the number of columns in the grid layout.
     *
     * @param int $cols The number of columns in the grid layout.
     * @return static
     */
    public function setCols(int $cols): static
    {
        $this->cols = $cols;

        return $this;
    }
}
