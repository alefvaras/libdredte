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

#[AsTwigComponent('block-tabs')]
class TabsComponent extends AbstractComponent
{
    /**
     * Array of tab configurations.
     *
     * Each tab contains title and content.
     *
     * @var array
     */
    private array $tabs = [];

    /**
     * Identifier of the active tab. First tab is active if null.
     *
     * @var string|null
     */
    private ?string $activeTab = null;

    /**
     * Layout orientation of tabs (horizontal, vertical).
     *
     * @var string
     */
    private string $position = 'horizontal';

    /**
     * Number of columns for vertical layout.
     *
     * @var int
     */
    private int $cols = 2;

    /**
     * Gets the array of tabs.
     *
     * @return array The array of tabs.
     */
    public function getTabs(): array
    {
        return $this->tabs;
    }

    /**
     * Sets the array of tabs.
     *
     * @param array $tabs The array of tabs.
     * @return static
     */
    public function setTabs(array $tabs): static
    {
        $this->tabs = $tabs;

        return $this;
    }

    /**
     * Gets the identifier of the active tab.
     *
     * @return string|null The identifier of the active tab.
     */
    public function getActiveTab(): ?string
    {
        return $this->activeTab;
    }

    /**
     * Sets the identifier of the active tab.
     *
     * @param string|null $activeTab The identifier of the active tab.
     * @return static
     */
    public function setActiveTab(?string $activeTab): static
    {
        $this->activeTab = $activeTab;

        return $this;
    }

    /**
     * Gets the layout orientation of tabs.
     *
     * @return string The layout orientation of tabs.
     */
    public function getPosition(): string
    {
        return $this->position;
    }

    /**
     * Sets the layout orientation of tabs.
     *
     * @param string $position The layout orientation of tabs.
     * @return static
     */
    public function setPosition(string $position): static
    {
        $this->position = $position;

        return $this;
    }

    /**
     * Gets the number of columns for vertical layout.
     *
     * @return int The number of columns for vertical layout.
     */
    public function getCols(): int
    {
        return $this->cols;
    }

    /**
     * Sets the number of columns for vertical layout.
     *
     * @param int $cols The number of columns for vertical layout.
     * @return static
     */
    public function setCols(int $cols): static
    {
        $this->cols = $cols;

        return $this;
    }
}
