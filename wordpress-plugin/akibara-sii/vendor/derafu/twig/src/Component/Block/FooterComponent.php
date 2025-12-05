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

#[AsTwigComponent('block-footer')]
class FooterComponent extends AbstractComponent
{
    /**
     * Title for the first column.
     *
     * @var string|null
     */
    private ?string $col1Title = null;

    /**
     * HTML content for the first column.
     *
     * @var string|null
     */
    private ?string $col1Html = null;

    /**
     * Array of links for the first column.
     *
     * @var array
     */
    private array $col1Links = [];

    /**
     * Array of social icons for the first column.
     *
     * @var array
     */
    private array $col1SocialIcons = [];

    /**
     * Title for the second column.
     *
     * @var string|null
     */
    private ?string $col2Title = null;

    /**
     * HTML content for the second column.
     *
     * @var string|null
     */
    private ?string $col2Html = null;

    /**
     * Array of links for the second column.
     *
     * @var array
     */
    private array $col2Links = [];

    /**
     * Array of social icons for the second column.
     *
     * @var array
     */
    private array $col2SocialIcons = [];

    /**
     * Title for the third column.
     *
     * @var string|null
     */
    private ?string $col3Title = null;

    /**
     * HTML content for the third column.
     *
     * @var string|null
     */
    private ?string $col3Html = null;

    /**
     * Array of links for the third column.
     *
     * @var array
     */
    private array $col3Links = [];

    /**
     * Array of social icons for the third column.
     *
     * @var array
     */
    private array $col3SocialIcons = [];

    /**
     * Title for the fourth column.
     *
     * @var string|null
     */
    private ?string $col4Title = null;

    /**
     * HTML content for the fourth column.
     *
     * @var string|null
     */
    private ?string $col4Html = null;

    /**
     * Array of links for the fourth column.
     *
     * @var array
     */
    private array $col4Links = [];

    /**
     * Array of social icons for the fourth column.
     *
     * @var array
     */
    private array $col4SocialIcons = [];

    /**
     * Text content for the left side of footer bottom.
     *
     * @var string|null
     */
    private ?string $leftText = null;

    /**
     * Text content for the right side of footer bottom.
     *
     * @var string|null
     */
    private ?string $rightText = null;

    /**
     * Whether social icons should be displayed in circular style.
     *
     * @var bool
     */
    private bool $socialIconsCircular = false;

    /**
     * Whether the footer should be full width.
     *
     * @var bool
     */
    private bool $fullWidth = false;

    /**
     * Gets the title for the first column.
     *
     * @return string|null The title for the first column
     */
    public function getCol1Title(): ?string
    {
        return $this->col1Title;
    }

    /**
     * Sets the title for the first column.
     *
     * @param string|null $col1Title The title for the first column
     * @return static
     */
    public function setCol1Title(?string $col1Title): static
    {
        $this->col1Title = $col1Title;

        return $this;
    }

    /**
     * Gets the HTML content for the first column.
     *
     * @return string|null The HTML content for the first column
     */
    public function getCol1Html(): ?string
    {
        return $this->col1Html;
    }

    /**
     * Sets the HTML content for the first column.
     *
     * @param string|null $col1Html The HTML content for the first column
     * @return static
     */
    public function setCol1Html(?string $col1Html): static
    {
        $this->col1Html = $col1Html;

        return $this;
    }

    /**
     * Gets the array of links for the first column.
     *
     * @return array The array of links for the first column
     */
    public function getCol1Links(): array
    {
        return $this->col1Links;
    }

    /**
     * Sets the array of links for the first column.
     *
     * @param array $col1Links The array of links for the first column
     * @return static
     */
    public function setCol1Links(array $col1Links): static
    {
        $this->col1Links = $col1Links;

        return $this;
    }

    /**
     * Gets the array of social icons for the first column.
     *
     * @return array The array of social icons for the first column
     */
    public function getCol1SocialIcons(): array
    {
        return $this->col1SocialIcons;
    }

    /**
     * Sets the array of social icons for the first column.
     *
     * @param array $col1SocialIcons The array of social icons for the first column
     * @return static
     */
    public function setCol1SocialIcons(array $col1SocialIcons): static
    {
        $this->col1SocialIcons = $col1SocialIcons;

        return $this;
    }

    /**
     * Gets the title for the second column.
     *
     * @return string|null The title for the second column
     */
    public function getCol2Title(): ?string
    {
        return $this->col2Title;
    }

    /**
     * Sets the title for the second column.
     *
     * @param string|null $col2Title The title for the second column
     * @return static
     */
    public function setCol2Title(?string $col2Title): static
    {
        $this->col2Title = $col2Title;

        return $this;
    }

    /**
     * Gets the HTML content for the second column.
     *
     * @return string|null The HTML content for the second column
     */
    public function getCol2Html(): ?string
    {
        return $this->col2Html;
    }

    /**
     * Sets the HTML content for the second column.
     *
     * @param string|null $col2Html The HTML content for the second column
     * @return static
     */
    public function setCol2Html(?string $col2Html): static
    {
        $this->col2Html = $col2Html;

        return $this;
    }

    /**
     * Gets the array of links for the second column.
     *
     * @return array The array of links for the second column
     */
    public function getCol2Links(): array
    {
        return $this->col2Links;
    }

    /**
     * Sets the array of links for the second column.
     *
     * @param array $col2Links The array of links for the second column
     * @return static
     */
    public function setCol2Links(array $col2Links): static
    {
        $this->col2Links = $col2Links;

        return $this;
    }

    /**
     * Gets the array of social icons for the second column.
     *
     * @return array The array of social icons for the second column
     */
    public function getCol2SocialIcons(): array
    {
        return $this->col2SocialIcons;
    }

    /**
     * Sets the array of social icons for the second column.
     *
     * @param array $col2SocialIcons The array of social icons for the second column
     * @return static
     */
    public function setCol2SocialIcons(array $col2SocialIcons): static
    {
        $this->col2SocialIcons = $col2SocialIcons;

        return $this;
    }

    /**
     * Gets the title for the third column.
     *
     * @return string|null The title for the third column
     */
    public function getCol3Title(): ?string
    {
        return $this->col3Title;
    }

    /**
     * Sets the title for the third column.
     *
     * @param string|null $col3Title The title for the third column
     * @return static
     */
    public function setCol3Title(?string $col3Title): static
    {
        $this->col3Title = $col3Title;

        return $this;
    }

    /**
     * Gets the HTML content for the third column.
     *
     * @return string|null The HTML content for the third column
     */
    public function getCol3Html(): ?string
    {
        return $this->col3Html;
    }

    /**
     * Sets the HTML content for the third column.
     *
     * @param string|null $col3Html The HTML content for the third column
     * @return static
     */
    public function setCol3Html(?string $col3Html): static
    {
        $this->col3Html = $col3Html;

        return $this;
    }

    /**
     * Gets the array of links for the third column.
     *
     * @return array The array of links for the third column
     */
    public function getCol3Links(): array
    {
        return $this->col3Links;
    }

    /**
     * Sets the array of links for the third column.
     *
     * @param array $col3Links The array of links for the third column
     * @return static
     */
    public function setCol3Links(array $col3Links): static
    {
        $this->col3Links = $col3Links;

        return $this;
    }

    /**
     * Gets the array of social icons for the third column.
     *
     * @return array The array of social icons for the third column
     */
    public function getCol3SocialIcons(): array
    {
        return $this->col3SocialIcons;
    }

    /**
     * Sets the array of social icons for the third column.
     *
     * @param array $col3SocialIcons The array of social icons for the third column
     * @return static
     */
    public function setCol3SocialIcons(array $col3SocialIcons): static
    {
        $this->col3SocialIcons = $col3SocialIcons;

        return $this;
    }

    /**
     * Gets the title for the fourth column.
     *
     * @return string|null The title for the fourth column
     */
    public function getCol4Title(): ?string
    {
        return $this->col4Title;
    }

    /**
     * Sets the title for the fourth column.
     *
     * @param string|null $col4Title The title for the fourth column
     * @return static
     */
    public function setCol4Title(?string $col4Title): static
    {
        $this->col4Title = $col4Title;

        return $this;
    }

    /**
     * Gets the HTML content for the fourth column.
     *
     * @return string|null The HTML content for the fourth column
     */
    public function getCol4Html(): ?string
    {
        return $this->col4Html;
    }

    /**
     * Sets the HTML content for the fourth column.
     *
     * @param string|null $col4Html The HTML content for the fourth column
     * @return static
     */
    public function setCol4Html(?string $col4Html): static
    {
        $this->col4Html = $col4Html;

        return $this;
    }

    /**
     * Gets the array of links for the fourth column.
     *
     * @return array The array of links for the fourth column
     */
    public function getCol4Links(): array
    {
        return $this->col4Links;
    }

    /**
     * Sets the array of links for the fourth column.
     *
     * @param array $col4Links The array of links for the fourth column
     * @return static
     */
    public function setCol4Links(array $col4Links): static
    {
        $this->col4Links = $col4Links;

        return $this;
    }

    /**
     * Gets the array of social icons for the fourth column.
     *
     * @return array The array of social icons for the fourth column
     */
    public function getCol4SocialIcons(): array
    {
        return $this->col4SocialIcons;
    }

    /**
     * Sets the array of social icons for the fourth column.
     *
     * @param array $col4SocialIcons The array of social icons for the fourth column
     * @return static
     */
    public function setCol4SocialIcons(array $col4SocialIcons): static
    {
        $this->col4SocialIcons = $col4SocialIcons;

        return $this;
    }

    /**
     * Gets the text content for the left side of footer bottom.
     *
     * @return string|null The text content for the left side of footer bottom
     */
    public function getLeftText(): ?string
    {
        return $this->leftText;
    }

    /**
     * Sets the text content for the left side of footer bottom.
     *
     * @param string|null $leftText The text content for the left side of footer bottom
     * @return static
     */
    public function setLeftText(?string $leftText): static
    {
        $this->leftText = $leftText;

        return $this;
    }

    /**
     * Gets the text content for the right side of footer bottom.
     *
     * @return string|null The text content for the right side of footer bottom
     */
    public function getRightText(): ?string
    {
        return $this->rightText;
    }

    /**
     * Sets the text content for the right side of footer bottom.
     *
     * @param string|null $rightText The text content for the right side of footer bottom
     * @return static
     */
    public function setRightText(?string $rightText): static
    {
        $this->rightText = $rightText;

        return $this;
    }

    /**
     * Gets whether social icons should be displayed in circular style.
     *
     * @return bool Whether social icons should be displayed in circular style
     */
    public function getSocialIconsCircular(): bool
    {
        return $this->socialIconsCircular;
    }

    /**
     * Sets whether social icons should be displayed in circular style.
     *
     * @param bool $socialIconsCircular Whether social icons should be displayed in circular style
     * @return static
     */
    public function setSocialIconsCircular(bool $socialIconsCircular): static
    {
        $this->socialIconsCircular = $socialIconsCircular;

        return $this;
    }

    /**
     * Gets whether the footer should be full width.
     *
     * @return bool Whether the footer should be full width
     */
    public function getFullWidth(): bool
    {
        return $this->fullWidth;
    }

    /**
     * Sets whether the footer should be full width.
     *
     * @param bool $fullWidth Whether the footer should be full width
     * @return static
     */
    public function setFullWidth(bool $fullWidth): static
    {
        $this->fullWidth = $fullWidth;

        return $this;
    }
}
