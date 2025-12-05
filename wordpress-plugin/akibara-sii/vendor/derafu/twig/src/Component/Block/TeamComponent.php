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

#[AsTwigComponent('block-team')]
class TeamComponent extends AbstractComponent
{
    /**
     * Array of team members configurations.
     *
     * Each member contains:
     * - image: Member image URL
     * - name: Member name
     * - role: Member role/position
     * - bio: Member biography (optional)
     * - links: Array of social/contact links:
     *   - icon: Link icon (optional)
     *   - text: Link text
     *   - url: Link URL
     *
     * @var array
     */
    private array $members = [];

    /**
     * Number of columns in the grid layout (1, 2, 3, 4, or 6).
     *
     * @var int
     */
    private int $cols = 4;

    /**
     * Content alignment (left, center, right).
     *
     * @var string
     */
    private string $align = 'center';

    /**
     * Gets the array of team members.
     *
     * @return array<int,array> The array of team members.
     */
    public function getMembers(): array
    {
        return $this->members;
    }

    /**
     * Sets the array of team members.
     *
     * @param array<int,array> $members The array of team members.
     * @return static
     */
    public function setMembers(array $members): static
    {
        $this->members = $members;

        return $this;
    }

    /**
     * Gets the number of columns in the grid layout.
     *
     * @return int The number of columns.
     */
    public function getCols(): int
    {
        return $this->cols;
    }

    /**
     * Sets the number of columns in the grid layout.
     *
     * @param int $cols The number of columns.
     * @return static
     */
    public function setCols(int $cols): static
    {
        if (!in_array($cols, [1, 2, 3, 4, 6], true)) {
            $this->error('Invalid number of columns. Must be 1, 2, 3, 4, or 6.');
        }

        $this->cols = $cols;

        return $this;
    }

    /**
     * Gets the content alignment.
     *
     * @return string The content alignment.
     */
    public function getAlign(): string
    {
        return $this->align;
    }

    /**
     * Sets the content alignment.
     *
     * @param string $align The content alignment.
     * @return static
     */
    public function setAlign(string $align): static
    {
        $this->align = $align;

        return $this;
    }
}
