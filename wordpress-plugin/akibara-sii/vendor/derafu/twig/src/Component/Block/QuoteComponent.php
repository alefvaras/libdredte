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
 * Quote Component for displaying testimonials, citations, or quotes.
 *
 * This component creates a styled quote block that can include the quote text,
 * author information, source reference, and an optional author image.
 */
#[AsTwigComponent('block-quote')]
class QuoteComponent extends AbstractComponent
{
    /**
     * The quote content.
     *
     * @var string
     */
    private string $content;

    /**
     * The quote author name.
     *
     * @var string
     */
    private string $author;

    /**
     * The author image URL.
     *
     * @var string|null
     */
    private ?string $image = null;

    /**
     * The quote note or reference.
     *
     * @var string|null
     */
    private ?string $note = null;

    /**
     * Gets the quote content.
     *
     * @return string The quote content
     */
    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * Sets the quote content.
     *
     * @param string $content The quote content
     * @return static
     */
    public function setContent(string $content): static
    {
        $this->content = $content;

        return $this;
    }

    /**
     * Gets the quote author name.
     *
     * @return string The quote author name
     */
    public function getAuthor(): string
    {
        return $this->author;
    }

    /**
     * Sets the quote author name.
     *
     * @param string $author The quote author name
     * @return static
     */
    public function setAuthor(string $author): static
    {
        $this->author = $author;

        return $this;
    }

    /**
     * Gets the author image URL.
     *
     * @return string|null The author image URL
     */
    public function getImage(): ?string
    {
        return $this->image;
    }

    /**
     * Sets the author image URL.
     *
     * @param string|null $image The author image URL
     * @return static
     */
    public function setImage(?string $image): static
    {
        $this->image = $image;

        return $this;
    }

    /**
     * Gets the quote note or reference.
     *
     * @return string|null The quote note or reference
     */
    public function getNote(): ?string
    {
        return $this->note;
    }

    /**
     * Sets the quote note or reference.
     *
     * @param string|null $note The quote note or reference
     * @return static
     */
    public function setNote(?string $note): static
    {
        $this->note = $note;

        return $this;
    }
}
