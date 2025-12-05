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

#[AsTwigComponent('block-testimonials')]
class TestimonialsComponent extends AbstractComponent
{
    /**
     * Array of testimonials configurations.
     *
     * Each testimonial contains:
     * - background: Background image URL
     * - content: Testimonial text
     * - author: Author name
     * - note: organization name (optional)
     * - url: Link URL (optional)
     * - image: Author image URL (optional)
     * - position: Card position (center, left, right)
     *
     * @var array
     */
    private array $testimonials = [];

    /**
     * Enable automatic slideshow.
     *
     * @var bool
     */
    private bool $autoplay = true;

    /**
     * Time between slides in milliseconds.
     *
     * @var int
     */
    private int $interval = 5000;

    /**
     * Show navigation controls.
     *
     * @var bool
     */
    private bool $showControls = true;

    /**
     * Show slide indicators.
     *
     * @var bool
     */
    private bool $showIndicators = true;

    /**
     * Maximum width of testimonial cards in pixels.
     *
     * @var int
     */
    private int $maxWidth = 600;

    /**
     * Background image brightness percentage.
     *
     * @var int
     */
    private int $brightness = 70;

    /**
     * Height of testimonial section in pixels.
     *
     * @var int
     */
    private int $height = 500;

    /**
     * Gets the array of testimonials.
     *
     * @return array<int,array> The array of testimonials.
     */
    public function getTestimonials(): array
    {
        return $this->testimonials;
    }

    /**
     * Sets the array of testimonials.
     *
     * @param array<int,array> $testimonials The array of testimonials.
     * @return static
     */
    public function setTestimonials(array $testimonials): static
    {
        $this->testimonials = $testimonials;

        return $this;
    }

    /**
     * Gets the autoplay setting.
     *
     * @return bool Whether autoplay is enabled.
     */
    public function getAutoplay(): bool
    {
        return $this->autoplay;
    }

    /**
     * Sets the autoplay setting.
     *
     * @param bool $autoplay Whether to enable autoplay.
     * @return static
     */
    public function setAutoplay(bool $autoplay): static
    {
        $this->autoplay = $autoplay;

        return $this;
    }

    /**
     * Gets the interval between slides.
     *
     * @return int The interval in milliseconds.
     */
    public function getInterval(): int
    {
        return $this->interval;
    }

    /**
     * Sets the interval between slides.
     *
     * @param int $interval The interval in milliseconds.
     * @return static
     */
    public function setInterval(int $interval): static
    {
        $this->interval = $interval;

        return $this;
    }

    /**
     * Gets the show controls setting.
     *
     * @return bool Whether controls are shown.
     */
    public function getShowControls(): bool
    {
        return $this->showControls;
    }

    /**
     * Sets the show controls setting.
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
     * Gets the show indicators setting.
     *
     * @return bool Whether indicators are shown.
     */
    public function getShowIndicators(): bool
    {
        return $this->showIndicators;
    }

    /**
     * Sets the show indicators setting.
     *
     * @param bool $showIndicators Whether to show indicators.
     * @return static
     */
    public function setShowIndicators(bool $showIndicators): static
    {
        $this->showIndicators = $showIndicators;

        return $this;
    }

    /**
     * Gets the maximum width of testimonial cards.
     *
     * @return int The maximum width in pixels.
     */
    public function getMaxWidth(): int
    {
        return $this->maxWidth;
    }

    /**
     * Sets the maximum width of testimonial cards.
     *
     * @param int $maxWidth The maximum width in pixels.
     * @return static
     */
    public function setMaxWidth(int $maxWidth): static
    {
        $this->maxWidth = $maxWidth;

        return $this;
    }

    /**
     * Gets the background image brightness.
     *
     * @return int The brightness percentage.
     */
    public function getBrightness(): int
    {
        return $this->brightness;
    }

    /**
     * Sets the background image brightness.
     *
     * @param int $brightness The brightness percentage.
     * @return static
     */
    public function setBrightness(int $brightness): static
    {
        $this->brightness = $brightness;

        return $this;
    }

    /**
     * Gets the height of the testimonial section.
     *
     * @return int The height in pixels.
     */
    public function getHeight(): int
    {
        return $this->height;
    }

    /**
     * Sets the height of the testimonial section.
     *
     * @param int $height The height in pixels.
     * @return static
     */
    public function setHeight(int $height): static
    {
        $this->height = $height;

        return $this;
    }
}
