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

#[AsTwigComponent('block-steps')]
class StepsComponent extends AbstractComponent
{
    /**
     * Array of steps with their configurations.
     *
     * Each step contains:
     * - icon: Font Awesome icon class (e.g. "fa-solid fa-list")
     * - title: Step title
     * - content: Step content (supports HTML)
     *
     * @var array
     */
    private array $steps = [];

    /**
     * Style of arrows connecting steps (curved, straight).
     *
     * @var string
     */
    private string $arrowType = 'curved';

    /**
     * Gets the array of steps.
     *
     * @return array<int,array> The array of steps.
     */
    public function getSteps(): array
    {
        return $this->steps;
    }

    /**
     * Sets the array of steps.
     *
     * @param array<int,array> $steps The array of steps.
     * @return static
     */
    public function setSteps(array $steps): static
    {
        $this->steps = $steps;

        return $this;
    }

    /**
     * Gets the style of arrows connecting steps.
     *
     * @return string The style of arrows connecting steps.
     */
    public function getArrowType(): string
    {
        return $this->arrowType;
    }

    /**
     * Sets the style of arrows connecting steps.
     *
     * @param string $arrowType The style of arrows connecting steps.
     * @return static
     */
    public function setArrowType(string $arrowType): static
    {
        $this->arrowType = $arrowType;

        return $this;
    }
}
