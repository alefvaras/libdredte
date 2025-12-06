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

#[AsTwigComponent('block-header')]
class HeaderComponent extends AbstractComponent
{
    /**
     * URL for the logo link.
     *
     * @var string
     */
    private string $logoUrl = '';

    /**
     * Path to the logo image file.
     *
     * @var string|null
     */
    private ?string $logoImage = null;

    /**
     * HTML content for the logo.
     *
     * @var string|null
     */
    private ?string $logoText = null;

    /**
     * Maximum width for the logo in pixels.
     *
     * @var int
     */
    private int $logoMaxWidth = 150;

    /**
     * Array of navigation items for the left side.
     *
     * @var array
     */
    private array $leftNav = [];

    /**
     * Array of navigation items for the right side.
     *
     * @var array
     */
    private array $rightNav = [];

    /**
     * Icon class for the call-to-action button.
     *
     * @var string|null
     */
    private ?string $ctaIcon = null;

    /**
     * Text for the call-to-action button.
     *
     * @var string|null
     */
    private ?string $ctaText = null;

    /**
     * URL for the call-to-action button.
     *
     * @var string|null
     */
    private ?string $ctaUrl = null;

    /**
     * Trigger type for dropdown menus ('click' or 'hover').
     *
     * @var string
     */
    private string $dropdownTrigger = 'click';

    /**
     * Position of the header ('normal' or 'fixed').
     *
     * @var string
     */
    private string $position = 'normal';

    /**
     * Background color for the header.
     *
     * @var string|null
     */
    private ?string $backgroundColor = null;

    /**
     * Whether the header should be full width.
     *
     * @var bool
     */
    private bool $fullWidth = false;

    /**
     * Process navigation items before component mount.
     */
    #[PreMount]
    public function preMount(array $data): array
    {
        if (isset($data['leftNav'])) {
            $data['leftNav'] = array_map([$this, 'processNavItem'], $data['leftNav']);
        }

        if (isset($data['rightNav'])) {
            $data['rightNav'] = array_map([$this, 'processNavItem'], $data['rightNav']);
        }

        return $data;
    }

    /**
     * Process a single navigation item to determine its type.
     */
    private function processNavItem(array $item): array
    {
        if (isset($item['sections'])) {
            $item['type'] = 'mega-dropdown';
        } elseif (isset($item['items'])) {
            $item['type'] = 'dropdown';
            $item['items'] = array_map(function ($subitem) {
                if (isset($subitem['items'])) {
                    $subitem['type'] = 'submenu';
                } elseif (!isset($subitem['type'])) {
                    $subitem['type'] = 'link';
                }
                return $subitem;
            }, $item['items']);
        } else {
            $item['type'] = 'link';
        }

        return $item;
    }

    /**
     * Get the logo URL.
     *
     * @return string The URL for the logo link.
     */
    public function getLogoUrl(): string
    {
        return $this->logoUrl;
    }

    /**
     * Set the logo URL.
     *
     * @param string $logoUrl The URL for the logo link.
     * @return static Returns the current instance for method chaining.
     */
    public function setLogoUrl(string $logoUrl): static
    {
        $this->logoUrl = $logoUrl;

        return $this;
    }

    /**
     * Get the logo image path.
     *
     * @return string|null The path to the logo image file.
     */
    public function getLogoImage(): ?string
    {
        return $this->logoImage;
    }

    /**
     * Set the logo image path.
     *
     * @param string|null $logoImage The path to the logo image file.
     * @return static Returns the current instance for method chaining.
     */
    public function setLogoImage(?string $logoImage): static
    {
        $this->logoImage = $logoImage;

        return $this;
    }

    /**
     * Get the logo text content.
     *
     * @return string|null The HTML content for the logo.
     */
    public function getLogoText(): ?string
    {
        return $this->logoText;
    }

    /**
     * Set the logo text content.
     *
     * @param string|null $logoText The HTML content for the logo.
     * @return static Returns the current instance for method chaining.
     */
    public function setLogoText(?string $logoText): static
    {
        $this->logoText = $logoText;

        return $this;
    }

    /**
     * Get the logo maximum width.
     *
     * @return int The maximum width for the logo in pixels.
     */
    public function getLogoMaxWidth(): int
    {
        return $this->logoMaxWidth;
    }

    /**
     * Set the logo maximum width.
     *
     * @param int $logoMaxWidth The maximum width for the logo in pixels.
     * @return static Returns the current instance for method chaining.
     */
    public function setLogoMaxWidth(int $logoMaxWidth): static
    {
        $this->logoMaxWidth = $logoMaxWidth;

        return $this;
    }

    /**
     * Get the left navigation items.
     *
     * @return array The array of navigation items for the left side.
     */
    public function getLeftNav(): array
    {
        return $this->leftNav;
    }

    /**
     * Set the left navigation items.
     *
     * @param array $leftNav The array of navigation items for the left side.
     * @return static Returns the current instance for method chaining.
     */
    public function setLeftNav(array $leftNav): static
    {
        $this->leftNav = $leftNav;

        return $this;
    }

    /**
     * Get the right navigation items.
     *
     * @return array The array of navigation items for the right side.
     */
    public function getRightNav(): array
    {
        return $this->rightNav;
    }

    /**
     * Set the right navigation items.
     *
     * @param array $rightNav The array of navigation items for the right side.
     * @return static Returns the current instance for method chaining.
     */
    public function setRightNav(array $rightNav): static
    {
        $this->rightNav = $rightNav;

        return $this;
    }

    /**
     * Get the call-to-action button icon.
     *
     * @return string|null The icon class for the call-to-action button.
     */
    public function getCtaIcon(): ?string
    {
        return $this->ctaIcon;
    }

    /**
     * Set the call-to-action button icon.
     *
     * @param string|null $ctaIcon The icon class for the call-to-action button.
     * @return static Returns the current instance for method chaining.
     */
    public function setCtaIcon(?string $ctaIcon): static
    {
        $this->ctaIcon = $ctaIcon;

        return $this;
    }

    /**
     * Get the call-to-action button text.
     *
     * @return string|null The text for the call-to-action button.
     */
    public function getCtaText(): ?string
    {
        return $this->ctaText;
    }

    /**
     * Set the call-to-action button text.
     *
     * @param string|null $ctaText The text for the call-to-action button.
     * @return static Returns the current instance for method chaining.
     */
    public function setCtaText(?string $ctaText): static
    {
        $this->ctaText = $ctaText;

        return $this;
    }

    /**
     * Get the call-to-action button URL.
     *
     * @return string|null The URL for the call-to-action button.
     */
    public function getCtaUrl(): ?string
    {
        return $this->ctaUrl;
    }

    /**
     * Set the call-to-action button URL.
     *
     * @param string|null $ctaUrl The URL for the call-to-action button.
     * @return static Returns the current instance for method chaining.
     */
    public function setCtaUrl(?string $ctaUrl): static
    {
        $this->ctaUrl = $ctaUrl;

        return $this;
    }

    /**
     * Get the dropdown trigger type.
     *
     * @return string The trigger type for dropdown menus ('click' or 'hover').
     */
    public function getDropdownTrigger(): string
    {
        return $this->dropdownTrigger;
    }

    /**
     * Set the dropdown trigger type.
     *
     * @param string $dropdownTrigger The trigger type for dropdown menus ('click' or 'hover').
     * @return static Returns the current instance for method chaining.
     */
    public function setDropdownTrigger(string $dropdownTrigger): static
    {
        $this->dropdownTrigger = $dropdownTrigger;

        return $this;
    }

    /**
     * Get the header position.
     *
     * @return string The position of the header ('normal' or 'fixed').
     */
    public function getPosition(): string
    {
        return $this->position;
    }

    /**
     * Set the header position.
     *
     * @param string $position The position of the header ('normal' or 'fixed').
     * @return static Returns the current instance for method chaining.
     */
    public function setPosition(string $position): static
    {
        $this->position = $position;

        return $this;
    }

    /**
     * Get the background color.
     *
     * @return string|null The background color for the header.
     */
    public function getBackgroundColor(): ?string
    {
        return $this->backgroundColor;
    }

    /**
     * Set the background color.
     *
     * @param string|null $backgroundColor The background color for the header.
     * @return static Returns the current instance for method chaining.
     */
    public function setBackgroundColor(?string $backgroundColor): static
    {
        $this->backgroundColor = $backgroundColor;

        return $this;
    }

    /**
     * Check if the header should be full width.
     *
     * @return bool Whether the header should be full width.
     */
    public function getFullWidth(): bool
    {
        return $this->fullWidth;
    }

    /**
     * Set whether the header should be full width.
     *
     * @param bool $fullWidth Whether the header should be full width.
     * @return static Returns the current instance for method chaining.
     */
    public function setFullWidth(bool $fullWidth): static
    {
        $this->fullWidth = $fullWidth;

        return $this;
    }
}
