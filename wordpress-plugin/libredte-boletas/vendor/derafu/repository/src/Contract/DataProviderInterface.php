<?php

declare(strict_types=1);

/**
 * Derafu: Repository - Lightweight File Data Source Management for PHP.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Repository\Contract;

use ArrayObject;
use Derafu\Config\Contract\ConfigurableInterface;
use Derafu\Repository\Exception\DataProviderException;

/**
 * Interface for repository data provider.
 */
interface DataProviderInterface extends ConfigurableInterface
{
    /**
     * Searches and returns data from a data source.
     *
     * Data is returned as an ArrayObject to be shared between different data
     * consumers that might require the same source.
     *
     * @param string $source Identifier of the requested source.
     * @return ArrayObject
     * @throws DataProviderException
     */
    public function fetch(string $source): ArrayObject;
}
