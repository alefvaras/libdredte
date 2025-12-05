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

use Derafu\Config\Contract\ConfigurableInterface;
use Derafu\Repository\Exception\ManagerException;

/**
 * Interface for entity manager.
 */
interface RepositoryManagerInterface extends ConfigurableInterface
{
    /**
     * Returns the repository associated with an entity class or repository
     * identifier.
     *
     * @param string $repository Entity class or repository identifier.
     * @return RepositoryInterface Requested repository.
     * @throws ManagerException
     */
    public function getRepository(string $repository): RepositoryInterface;
}
