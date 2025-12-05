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
 * Scrollspy Bootstrap Component
 *
 * Allows creating a navigation by anchor points with smooth scrolling.
 */
#[AsTwigComponent('block-scrollspy')]
class ScrollspyComponent extends AbstractComponent
{
    /**
     * Array of scrollspy sections.
     *
     * Each section should contain:
     *   - `id`: Unique identifier for the section
     *   - `title`: Navigation item text
     *   - `content`: Section content
     *   - `icon`: (Optional) FontAwesome icon class
     *   - `badge`: (Optional) Badge text
     *
     * @var array<int,array>
     */
    private array $sections = [];

    /**
     * Orientation of the scrollspy.
     * Values: 'vertical', 'horizontal'.
     */
    private string $orientation = 'vertical';

    /**
     * Height of the scrollspy.
     * Values: '100vh', '100%', 'auto'.
     */
    private string $height = '100vh';

    /**
     * Number of columns for the navigation list (1-11).
     * Content will automatically use the remaining columns.
     */
    private int $cols = 4;

    /**
     * Custom inline styles to add to the component.
     */
    private ?string $style = null;

    /**
     * Gets the scrollspy sections.
     *
     * @return array<int,array> The scrollspy sections
     */
    public function getSections(): array
    {
        return $this->sections;
    }

    /**
     * Sets the scrollspy sections.
     *
     * @param array<int,array> $sections The scrollspy sections
     * @return static
     */
    public function setSections(array $sections): static
    {
        $this->sections = $sections;

        return $this;
    }

    /**
     * Gets the scrollspy orientation.
     *
     * @return string The scrollspy orientation
     */
    public function getOrientation(): string
    {
        return $this->orientation;
    }

    /**
     * Sets the scrollspy orientation.
     *
     * @param string $orientation The scrollspy orientation
     * @return static
     */
    public function setOrientation(string $orientation): static
    {
        $this->orientation = $orientation;

        return $this;
    }

    /**
     * Gets the scrollspy height.
     *
     * @return string The scrollspy height
     */
    public function getHeight(): string
    {
        return $this->height;
    }

    /**
     * Sets the scrollspy height.
     *
     * @param string $height The scrollspy height
     * @return static
     */
    public function setHeight(string $height): static
    {
        $this->height = $height;

        return $this;
    }

    /**
     * Gets the number of columns.
     *
     * @return int The number of columns
     */
    public function getCols(): int
    {
        return $this->cols;
    }

    /**
     * Sets the number of columns.
     *
     * @param int $cols The number of columns
     * @return static
     */
    public function setCols(int $cols): static
    {
        $this->cols = $cols;

        return $this;
    }

    /**
     * Gets the custom inline styles.
     *
     * @return string|null The custom inline styles
     */
    public function getStyle(): ?string
    {
        return $this->style;
    }

    /**
     * Sets the custom inline styles.
     *
     * @param string|null $style The custom inline styles
     * @return static
     */
    public function setStyle(?string $style): static
    {
        $this->style = $style;

        return $this;
    }

    /**
     * Validate and prepare sections before rendering.
     *
     * @return void
     */
    public function mount(): void
    {
        // Provide default structure if no sections are passed.
        if (empty($this->sections)) {
            $this->sections = [
                [
                    'id' => 'default-section',
                    'title' => 'Default Section',
                    'content' => 'Default content for scrollspy section.',
                    'icon' => null,
                    'badge' => null,
                ],
            ];
        }

        // Process and validate all sections.
        $this->sections = array_map(function ($section) {
            // Generate ID if not provided.
            if (empty($section['id'])) {
                $section['id'] = 'section-' . uniqid();
            }

            // Ensure each section has all required keys.
            return [
                'id' => $section['id'],
                'title' => $section['title'] ?? 'Untitled',
                'content' => $section['content'] ?? '',
                'icon' => $section['icon'] ?? null,
                'badge' => $section['badge'] ?? null,
            ];
        }, $this->sections);

        // Validate orientation.
        $validOrientations = ['vertical', 'horizontal'];
        $this->orientation = in_array($this->orientation, $validOrientations)
            ? $this->orientation
            : 'vertical';

        // Validate cols (between 1 and 11).
        $this->cols = max(1, min(11, (int) $this->cols));
    }
}
