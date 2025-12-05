<?php

declare(strict_types=1);

/**
 * Derafu: Repository - Lightweight File Data Source Management for PHP.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Repository;

use ArrayAccess;
use ArrayObject;
use Derafu\Container\Abstract\AbstractContainer;
use Derafu\Repository\Contract\RepositoryInterface;
use Derafu\Support\Arr;
use Derafu\Support\Factory;
use Doctrine\Common\Collections\Criteria;
use InvalidArgumentException;
use stdClass;

/**
 * Class for object/entity repositories.
 *
 * Provides standard methods to access and search objects/entities from a data
 * source.
 */
class Repository extends AbstractContainer implements RepositoryInterface
{
    /**
     * Entity class where data obtained through the repository will be placed.
     *
     * @var string
     */
    protected string $entityClass = stdClass::class;

    /**
     * Repository constructor.
     *
     * @param string|array|ArrayAccess|ArrayObject $source Data array or PHP
     * file path.
     * @param string|null $entityClass Entity class associated with the
     * repository.
     * @param string|null $idAttribute Name of the ID attribute that must be
     * ensured to exist in elements loaded from the repository.
     */
    public function __construct(
        string|array|ArrayAccess|ArrayObject $source,
        ?string $entityClass = null,
        ?string $idAttribute = null
    ) {
        if ($entityClass !== null) {
            $this->entityClass = $entityClass;
        }

        $this->load($source, $idAttribute);
    }

    /**
     * Loads repository data.
     *
     * @param string|array|ArrayAccess|ArrayObject $source
     * @param string|null $idAttribute
     * @return void
     */
    protected function load(
        string|array|ArrayAccess|ArrayObject $source,
        ?string $idAttribute = null
    ): void {
        $data = is_string($source) ? require $source : $source;
        if (!is_array($data)) {
            $data = $this->createFrom($data)->toArray();
        }
        if ($idAttribute && is_array($data)) {
            $data = Arr::ensureIdInElements($source, $idAttribute);
        }
        $this->data = $this->createFrom($data);
    }

    /**
     * {@inheritDoc}
     */
    public function find($id, $lockMode = null, $lockVersion = null): ?object
    {
        if (!is_string($id) && !is_int($id)) {
            throw new InvalidArgumentException(sprintf(
                'In method %s:find($id) an $id of type %s was passed and only string and int are allowed.',
                static::class,
                get_debug_type($id)
            ));
        }

        return isset($this->data[$id])
            ? $this->createEntity($this->data[$id])
            : null
        ;
    }

    /**
     * {@inheritDoc}
     */
    public function findAll(): array
    {
        return array_values($this->data->map(
            fn ($item) => $this->createEntity($item)
        )->toArray());
    }

    /**
     * {@inheritDoc}
     */
    public function findBy(
        array $criteria,
        ?array $orderBy = null,
        ?int $limit = null,
        ?int $offset = null
    ): array {
        $results = $this->data->filter(
            fn ($item) => $this->matchCriteria($item, $criteria)
        )->toArray();

        if ($orderBy) {
            $results = $this->applyOrderBy($results, $orderBy);
        }

        if ($offset !== null || $limit !== null) {
            $offset = $offset ?: 0;
            if ($offset < 0) {
                throw new InvalidArgumentException('Offset cannot be negative.');
            }

            if ($limit !== null && $limit < 0) {
                throw new InvalidArgumentException('Limit cannot be negative.');
            }

            $results = array_slice($results, $offset, $limit);
        }

        return array_values(array_map(
            fn ($item) => $this->createEntity($item),
            $results
        ));
    }

    /**
     * {@inheritDoc}
     */
    public function findOneBy(array $criteria, ?array $orderBy = null): ?object
    {
        $results = $this->findBy($criteria, $orderBy, 1);

        return empty($results) ? null : reset($results);
    }

    /**
     * {@inheritDoc}
     */
    public function count(array $criteria = []): int
    {
        if (empty($criteria)) {
            return count($this->data);
        }

        $results = $this->data->filter(
            fn ($item) => $this->matchCriteria($item, $criteria)
        );

        return $results->count();
    }

    /**
     * {@inheritDoc}
     */
    public function findByCriteria(Criteria $criteria): array
    {
        return parent::matching($criteria)->map(
            fn ($item) => $this->createEntity($item)
        )->getValues();
    }

    /**
     * {@inheritDoc}
     */
    public function getClassName(): string
    {
        return $this->entityClass;
    }

    /**
     * Checks if an item meets the search criteria.
     */
    protected function matchCriteria(array $item, array $criteria): bool
    {
        foreach ($criteria as $field => $value) {
            if (!is_array($value)) {
                $value = [$value];
            }
            if (!isset($item[$field]) || !in_array($item[$field], $value)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Orders results according to specified criteria.
     *
     * @param array $results Results to order.
     * @param array $orderBy Ordering criteria ['field' => 'ASC|DESC'].
     * @return array Ordered results.
     */
    protected function applyOrderBy(array $results, array $orderBy): array
    {
        uasort($results, function ($a, $b) use ($orderBy) {
            foreach ($orderBy as $field => $direction) {
                if (!isset($a[$field]) || !isset($b[$field])) {
                    continue;
                }

                $compare = $direction === 'DESC'
                    ? -1 * ($a[$field] <=> $b[$field])
                    : $a[$field] <=> $b[$field];

                if ($compare !== 0) {
                    return $compare;
                }
            }
            return 0;
        });

        return $results;
    }

    /**
     * Creates an entity from data.
     *
     * @param array $data Data that will be assigned to the entity.
     * @return object Entity instance with loaded data.
     */
    protected function createEntity(array $data): object
    {
        return Factory::create($data, $this->entityClass);
    }
}
