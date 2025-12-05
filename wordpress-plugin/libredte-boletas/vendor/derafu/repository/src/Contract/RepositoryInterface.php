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

use Derafu\Container\Contract\ContainerInterface;
use Doctrine\Common\Collections\Criteria;

/**
* Interface for object/entity repositories.
*
* Provides standard methods to access and search objects/entities from a data
* source.
*/
interface RepositoryInterface extends ContainerInterface
{
    /**
     * Finds an object by its identifier.
     *
     * @param mixed $id Object identifier
     * @param mixed $lockMode Not used in this implementation.
     * @param mixed $lockVersion Not used in this implementation.
     * @return object|null The found object or null if it doesn't exist
     */
    public function find($id, $lockMode = null, $lockVersion = null): ?object;

    /**
     * Finds all objects in the repository.
     *
     * @return object[] Array of found objects
     */
    public function findAll(): array;

    /**
     * Finds objects according to specific criteria.
     *
     * @param array $criteria Search criteria in format ['field' => 'value'].
     * @param array|null $orderBy Ordering criteria ['field' => 'ASC|DESC'].
     * @param int|null $limit Maximum number of results to return.
     * @param int|null $offset Number of results to skip.
     * @return object[] Array of objects that meet the criteria.
     */
    public function findBy(
        array $criteria,
        ?array $orderBy = null,
        ?int $limit = null,
        ?int $offset = null
    ): array;

    /**
     * Finds a single object according to specific criteria.
     *
     * @param array $criteria Search criteria in format ['field' => 'value'].
     * @param array|null $orderBy Ordering criteria ['field' => 'ASC|DESC'].
     * @return object|null The first object that meets the criteria or null if
     * it doesn't exist.
     */
    public function findOneBy(array $criteria, ?array $orderBy = null): ?object;

    /**
     * Returns the total number of objects in the repository.
     *
     * @param array $criteria Criteria when counting in format ['field' => 'value'].
     * @return int Number of objects.
     */
    public function count(array $criteria = []): int;

    /**
     * Applies a criterion to filter stored entities.
     *
     * This method allows filtering and ordering entities in storage according
     * to conditions defined in a `Criteria` object.
     *
     * The result is an array containing only entities that meet the conditions.
     *
     * @param Criteria $criteria The `Criteria` object that defines the
     * conditions, order and limits of the results.
     * @return array An array with entities that meet the criterion.
     * @see \Doctrine\Common\Collections\Criteria
     * @see \Doctrine\Common\Collections\ArrayCollection
     */
    public function findByCriteria(Criteria $criteria): array;

    /**
     * Returns the name of the class that the repository manages.
     *
     * @return string
     */
    public function getClassName(): string;
}
