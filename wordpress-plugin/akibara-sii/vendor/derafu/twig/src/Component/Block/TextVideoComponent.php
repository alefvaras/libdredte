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
use Symfony\UX\TwigComponent\Attribute\PreMount;

#[AsTwigComponent('block-text-video')]
class TextVideoComponent extends AbstractComponent
{
    /**
     * Position of the video (right, left).
     *
     * @var string
     */
    private string $videoPosition = 'right';

    /**
     * Number of columns for text section (Bootstrap grid).
     *
     * @var int
     */
    private int $cols = 7;

    /**
     * Title of the text section.
     *
     * @var string
     */
    private string $title;

    /**
     * Main content text (supports paragraphs).
     *
     * @var string
     */
    private string $content;

    /**
     * Array of button configurations.
     * Each button contains: {text, url}
     *
     * @var array
     */
    private array $buttons = [];

    /**
     * Video URL.
     *
     * @var string
     */
    private string $video;

    /**
     * Video aspect ratio (16:9, 4:3, 1:1).
     *
     * @var string
     */
    private string $aspectRatio = '16:9';

    /**
     * Show video player controls.
     *
     * @var bool
     */
    private bool $showControls = true;

    /**
     * Use privacy-enhanced mode (youtube-nocookie.com).
     *
     * @var bool
     */
    private bool $enablePrivacy = true;

    /**
     * Gets the position of the video.
     *
     * @return string The video position.
     */
    public function getVideoPosition(): string
    {
        return $this->videoPosition;
    }

    /**
     * Sets the position of the video.
     *
     * @param string $videoPosition The video position.
     * @return static
     */
    public function setVideoPosition(string $videoPosition): static
    {
        $this->videoPosition = $videoPosition;

        return $this;
    }

    /**
     * Gets the number of columns for text section.
     *
     * @return int The number of columns.
     */
    public function getCols(): int
    {
        return $this->cols;
    }

    /**
     * Sets the number of columns for text section.
     *
     * @param int $cols The number of columns.
     * @return static
     */
    public function setCols(int $cols): static
    {
        $this->cols = $cols;

        return $this;
    }

    /**
     * Gets the title of the text section.
     *
     * @return string The title.
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * Sets the title of the text section.
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
     * Gets the main content text.
     *
     * @return string The content.
     */
    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * Sets the main content text.
     *
     * @param string $content The content.
     * @return static
     */
    public function setContent(string $content): static
    {
        $this->content = $content;

        return $this;
    }

    /**
     * Gets the array of button configurations.
     *
     * @return array<int,array> The array of buttons.
     */
    public function getButtons(): array
    {
        return $this->buttons;
    }

    /**
     * Sets the array of button configurations.
     *
     * @param array<int,array> $buttons The array of buttons.
     * @return static
     */
    public function setButtons(array $buttons): static
    {
        $this->buttons = $buttons;

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
     * Gets the video aspect ratio.
     *
     * @return string The aspect ratio.
     */
    public function getAspectRatio(): string
    {
        return $this->aspectRatio;
    }

    /**
     * Sets the video aspect ratio.
     *
     * @param string $aspectRatio The aspect ratio.
     * @return static
     */
    public function setAspectRatio(string $aspectRatio): static
    {
        $this->aspectRatio = $aspectRatio;

        return $this;
    }

    /**
     * Gets whether video controls are shown.
     *
     * @return bool Whether controls are shown.
     */
    public function getShowControls(): bool
    {
        return $this->showControls;
    }

    /**
     * Sets whether video controls are shown.
     *
     * @param bool $showControls Whether to show controls.
     * @return static
     */
    public function setShowControls(bool $showControls): static
    {
        $this->showControls = $showControls;

        return $this;
    }

    /**
     * Gets whether privacy-enhanced mode is enabled.
     *
     * @return bool Whether privacy mode is enabled.
     */
    public function getEnablePrivacy(): bool
    {
        return $this->enablePrivacy;
    }

    /**
     * Sets whether privacy-enhanced mode is enabled.
     *
     * @param bool $enablePrivacy Whether to enable privacy mode.
     * @return static
     */
    public function setEnablePrivacy(bool $enablePrivacy): static
    {
        $this->enablePrivacy = $enablePrivacy;

        return $this;
    }

    /**
     * Process component data before mount.
     *
     * Converts YouTube URL to embed format and validates/generates ID.
     *
     * @param array $data The component data.
     * @return array The processed component data.
     */
    #[PreMount]
    public function preMount(array $data): array
    {
        // YouTube URL processing (existing logic).
        if (isset($data['video'])) {
            preg_match(
                '/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/',
                $data['video'],
                $matches
            );
            $videoId = $matches[1] ?? '';

            $privacy = $data['enablePrivacy'] ?? true;
            $base = $privacy
                ? 'https://www.youtube-nocookie.com/embed/'
                : 'https://www.youtube.com/embed/'
            ;

            $data['video'] = $base . $videoId;
        }

        return $data;
    }
}
