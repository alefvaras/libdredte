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

#[AsTwigComponent('block-box')]
class BoxComponent extends AbstractComponent
{
    /**
     * Title of the box component.
     *
     * @var string
     */
    private string $title;

    /**
     * Content text of the box (supports HTML).
     *
     * @var string|null
     */
    private ?string $content = null;

    /**
     * Gets the title of the box.
     *
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * Sets the title of the box.
     *
     * @param string $title The title of the box.
     * @return static
     */
    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Gets the content of the box.
     *
     * @return string|null
     */
    public function getContent(): ?string
    {
        return $this->content;
    }

    /**
     * Sets the content of the box.
     *
     * @param string $content The content of the box.
     * @return static
     */
    public function setContent(string $content): static
    {
        $this->content = $content;

        return $this;
    }
}
