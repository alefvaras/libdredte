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

#[AsTwigComponent('block-stats')]
class StatsComponent extends AbstractComponent
{
    /**
     * Array of statistics configurations.
     * Each stat contains: {value, text}
     *
     * @var array<int,array{value: string, text: string}>
     */
    private array $stats = [];

    /**
     * Position of the text relative to numbers (bottom, top).
     *
     * @var string
     */
    private string $textPosition = 'bottom';

    /**
     * Gets the array of statistics.
     *
     * @return array<int,array{value: string, text: string}> The array of statistics
     */
    public function getStats(): array
    {
        return $this->stats;
    }

    /**
     * Sets the array of statistics.
     *
     * @param array<int,array{value: string, text: string}> $stats The array of statistics.
     * @return static
     */
    public function setStats(array $stats): static
    {
        $this->stats = $stats;

        return $this;
    }

    /**
     * Gets the position of the text relative to numbers.
     *
     * @return string The position of the text relative to numbers.
     */
    public function getTextPosition(): string
    {
        return $this->textPosition;
    }

    /**
     * Sets the position of the text relative to numbers.
     *
     * @param string $textPosition The position of the text relative to numbers.
     * @return static
     */
    public function setTextPosition(string $textPosition): static
    {
        $this->textPosition = $textPosition;

        return $this;
    }
}
