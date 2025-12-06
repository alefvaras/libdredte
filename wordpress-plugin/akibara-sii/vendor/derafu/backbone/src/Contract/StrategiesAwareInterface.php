<?php

declare(strict_types=1);

/**
 * Derafu: Backbone - The Architectural Spine for PHP Libraries.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Backbone\Contract;

use Derafu\Backbone\Exception\StrategyNotFoundException;

/**
 * Interface for the services that use strategies.
 */
interface StrategiesAwareInterface
{
    /**
     * Gets a strategy by its name.
     *
     * @param string $name The name of the strategy.
     * @return StrategyInterface The strategy.
     * @throws StrategyNotFoundException If the strategy is not registered.
     */
    public function getStrategy(string $name): StrategyInterface;

    /**
     * Gets all strategies.
     *
     * @return array<string, StrategyInterface> The list of strategies.
     */
    public function getStrategies(): array;

    /**
     * Sets the strategies.
     *
     * @param array<string, StrategyInterface>|iterable $strategies The list of strategies.
     * @return static The instance.
     */
    public function setStrategies(iterable $strategies): static;
}
