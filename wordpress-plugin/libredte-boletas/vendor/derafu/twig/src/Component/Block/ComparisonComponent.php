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

#[AsTwigComponent('block-comparison')]
class ComparisonComponent extends AbstractComponent
{
    /**
     * Array of plans with their configurations.
     *
     * Each plan contains:
     * - title: Plan title
     * - price: Plan price
     * - period: Billing period (/month, /year, etc)
     * - features: Array of features:
     *   - text: Feature text
     *   - value: Specific value or true/false
     *   - tooltip: Optional tooltip
     * - button_text: Button text
     * - button_url: Button URL
     * - highlighted: true/false to highlight the plan
     *
     * @var array
     */
    private array $plans = [];

    /**
     * Gets the array of plans.
     *
     * @return array The array of plans with their configurations.
     */
    public function getPlans(): array
    {
        return $this->plans;
    }

    /**
     * Sets the array of plans.
     *
     * @param array $plans The array of plans with their configurations.
     * @return static
     */
    public function setPlans(array $plans): static
    {
        $this->plans = $plans;

        return $this;
    }
}
