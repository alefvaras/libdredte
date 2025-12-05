<?php

declare(strict_types=1);

/**
 * Derafu: Backbone - The Architectural Spine for PHP Libraries.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Backbone\Trait;

use Derafu\Backbone\Contract\StrategyInterface;
use Derafu\Backbone\Exception\StrategyException;

/**
 * Trait for the services that use strategies.
 */
trait StrategiesAwareTrait
{
    /**
     * Strategies that the service uses.
     *
     * @var StrategyInterface[]
     */
    protected array $strategies = [];

    /**
     * Gets a strategy by its name.
     *
     * @param string $strategy
     * @return StrategyInterface
     */
    public function getStrategy(string $strategy): StrategyInterface
    {
        $strategies = [$strategy];
        if (!str_contains($strategy, '.')) {
            $strategies[] = 'default.' . $strategy;
        }

        foreach ($strategies as $name) {
            if (isset($this->strategies[$name])) {
                return $this->strategies[$name];
            }
        }

        throw new StrategyException(sprintf(
            'Strategy %s not found in service %s (%s).',
            $strategy,
            $this->getName(),
            $this->getId(),
        ));
    }

    /**
     * Gets all strategies.
     *
     * @return StrategyInterface[]
     */
    public function getStrategies(): array
    {
        return $this->strategies;
    }

    /**
     * Sets the strategies.
     *
     * @param array|iterable $strategies
     * @return static
     */
    public function setStrategies(iterable $strategies): static
    {
        $this->strategies = is_array($strategies)
            ? $strategies
            : iterator_to_array($strategies)
        ;

        return $this;
    }
}
