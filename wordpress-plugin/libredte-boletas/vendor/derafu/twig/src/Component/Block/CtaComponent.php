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

#[AsTwigComponent('block-cta')]
class CtaComponent extends AbstractComponent
{
    /**
     * Main title of the CTA.
     *
     * @var string
     */
    private string $title;

    /**
     * Content text of the CTA.
     *
     * @var string
     */
    private string $content;

    /**
     * Text to display on the CTA button.
     *
     * @var string
     */
    private string $button_text;

    /**
     * URL for the CTA button.
     *
     * @var string
     */
    private string $button_url;

    /**
     * Number of columns for the text section (Bootstrap grid).
     *
     * @var int
     */
    private int $cols = 8;

    /**
     * Get the main title of the CTA.
     *
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * Set the main title of the CTA.
     *
     * @param string $title
     * @return static
     */
    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get the content text of the CTA.
     *
     * @return string
     */
    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * Set the content text of the CTA.
     *
     * @param string $content
     * @return static
     */
    public function setContent(string $content): static
    {
        $this->content = $content;

        return $this;
    }

    /**
     * Get the text to display on the CTA button.
     *
     * @return string
     */
    public function getButtonText(): string
    {
        return $this->button_text;
    }

    /**
     * Set the text to display on the CTA button.
     *
     * @param string $button_text
     * @return static
     */
    public function setButtonText(string $button_text): static
    {
        $this->button_text = $button_text;

        return $this;
    }

    /**
     * Get the URL for the CTA button.
     *
     * @return string
     */
    public function getButtonUrl(): string
    {
        return $this->button_url ?? '';
    }

    /**
     * Set the URL for the CTA button.
     *
     * @param string $button_url
     * @return static
     */
    public function setButtonUrl(string $button_url): static
    {
        $this->button_url = $button_url;

        return $this;
    }

    /**
     * Get the number of columns for the text section.
     *
     * @return int
     */
    public function getCols(): int
    {
        return $this->cols;
    }

    /**
     * Set the number of columns for the text section.
     *
     * @param int $cols
     * @return static
     */
    public function setCols(int $cols): static
    {
        $this->cols = $cols;

        return $this;
    }
}
