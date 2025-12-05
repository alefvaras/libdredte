<?php

declare(strict_types=1);

/**
 * Derafu: Repository - Lightweight File Data Source Management for PHP.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Repository\Service;

use Derafu\Config\Trait\ConfigurableTrait;
use Derafu\Repository\Contract\DataProviderInterface;
use Derafu\Repository\Contract\RepositoryInterface;
use Derafu\Repository\Contract\RepositoryManagerInterface;
use Derafu\Repository\Entity;
use Derafu\Repository\Exception\ManagerException;
use Derafu\Repository\Mapping as DEM;
use Derafu\Repository\Repository;
use Exception;
use ReflectionClass;

/**
 * Repository management service.
 */
class RepositoryManager implements RepositoryManagerInterface
{
    use ConfigurableTrait;

    /**
     * Interface suffix.
     *
     * @var string
     */
    private const ENTITY_INTERFACE_SUFFIX = 'Interface';

    /**
     * Interface namespace.
     *
     * Important: only the immediately superior level.
     *
     * @var string
     */
    private const ENTITY_INTERFACE_NAMESPACE = 'Contract';

    /**
     * Entity class suffix.
     *
     * Important: no suffixes are used in entities, but it's standardized here
     * in the constant.
     *
     * @var string
     */
    private const ENTITY_CLASS_SUFFIX = ''; // Empty on purpose.

    /**
     * Entity namespace.
     *
     * Important: only the immediately superior level.
     *
     * @var string
     */
    private const ENTITY_CLASS_NAMESPACE = 'Entity';

    /**
     * Worker configuration schema.
     *
     * @var array
     */
    protected array $configurationSchema = [
        'entityClass' => [
            'types' => 'string',
            'default' => Entity::class,
        ],
        'repositoryClass' => [
            'types' => 'string',
            'default' => Repository::class,
        ],
    ];

    /**
     * List of repositories that have already been loaded from their data
     * sources.
     *
     * @var array<string,RepositoryInterface>
     */
    private array $loaded = [];

    /**
     * Worker constructor.
     *
     * @param DataProviderInterface $dataProvider
     */
    public function __construct(
        private DataProviderInterface $dataProvider
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function getRepository(string $repository): RepositoryInterface
    {
        // If the repository is not loaded it's loaded.
        if (!isset($this->loaded[$repository])) {
            try {
                $this->loaded[$repository] = $this->loadRepository($repository);
            } catch (Exception $e) {
                throw new ManagerException($e->getMessage());
            }
        }

        // Return the requested repository.
        return $this->loaded[$repository];
    }

    /**
     * Loads a repository with data from a data source.
     *
     * @param string $repository
     * @return RepositoryInterface
     */
    private function loadRepository(string $repository): RepositoryInterface
    {
        // Resolve the repository that should be created.
        $entityClass = $this->resolveEntityClass($repository);
        $repositoryClass = $this->resolveRepositoryClass($entityClass);

        // If the repository implements RepositoryInterface it's a repository
        // with data that is obtained from DatasourceProvider and must be loaded
        // to the repository (in memory).
        if (in_array(RepositoryInterface::class, class_implements($repositoryClass))) {
            $data = $this->dataProvider->fetch($repository);
            $instance = new $repositoryClass($data, $entityClass);
        }

        // If the repository is another type of class it's instantiated without
        // data and it's expected that the repository resolves its loading (e.g.
        // from a database).
        else {
            // TODO: Improve RepositoryInterface return with this case.
            $instance = new $repositoryClass();
        }

        // Assign the repository instance as loaded and return.
        $this->loaded[$repository] =  $instance;
        return $this->loaded[$repository];
    }

    /**
     * Determines the entity class that should be used with the repository.
     *
     * The repository identifier can be the FQCN of the entity class (ideal) or
     * a generic identifier that will be resolved to the entity class configured
     * in the worker.
     *
     * If the repository identifier is an interface it's expected:
     *
     *   - The interface is within a "Contract" namespace.
     *   - The interface has "Interface" as suffix.
     *   - The entity is within the "Entity" namespace without suffix.
     *
     * @param string $repository Repository identifier.
     * @return string Entity class for the repository.
     */
    private function resolveEntityClass(string $repository): string
    {
        // If the repository identifier "looks like" a class it's assumed to be
        // the entity class or an entity interface in the same namespace.
        if (str_contains($repository, '\\')) {
            $entityClass = $this->guessEntityClass($repository);
        }

        // The default entity class is returned when the identifier is not a
        // class. If it doesn't have "\" then it doesn't have a namespace, in
        // this case it's assumed it's not a class.
        else {
            $entityClass = $this->getConfiguration()->get('entityClass');
        }

        // Throw error if the class doesn't exist as it might have been
        // misspelled by the programmer.
        if (!class_exists($entityClass)) {
            throw new ManagerException(sprintf(
                'Entity class %s does not exist. Could it be misspelled?',
                $entityClass
            ));
        }

        // Return the entity class.
        return $entityClass;
    }

    /**
     * Guesses the entity class in case the input class is an interface.
     *
     * @param string $class
     * @return string
     */
    private function guessEntityClass(string $class): string
    {
        // If the class is an interface it's assumed to be an entity class in
        // the same namespace. This is rigid and requires a format for the class
        // and interface FQCN. For now it's sufficient.
        if (str_ends_with($class, self::ENTITY_INTERFACE_SUFFIX)) {
            $length = strlen($class) - strlen(self::ENTITY_INTERFACE_SUFFIX);
            return str_replace(
                '\\' . self::ENTITY_INTERFACE_NAMESPACE .  '\\',
                '\\' . self::ENTITY_CLASS_NAMESPACE  . '\\',
                substr($class, 0, $length)
            ) . self::ENTITY_CLASS_SUFFIX;
        }

        // Return the same class, as it doesn't have the expected format to
        // guess the entity class.
        return $class;
    }

    /**
     * Determines the repository class that should be used for an entity.
     *
     * If the entity class doesn't provide information about its associated
     * repository, the repository class configured in the worker will be
     * returned.
     *
     * @param string $entityClass Entity class.
     * @return string Repository class.
     */
    private function resolveRepositoryClass(string $entityClass): string
    {
        // Try to get the repository class from the entity's static method
        // getRepositoryClass().
        if (method_exists($entityClass, 'getRepositoryClass')) {
            $repositoryClass = call_user_func([$entityClass, 'getRepositoryClass']);
            if ($repositoryClass) {
                return $repositoryClass;
            }
        }

        // Try to get the repository class from a PHP attribute of the entity
        // class.
        $reflectionClass = new ReflectionClass($entityClass);
        $attributes = $reflectionClass->getAttributes(DEM\Entity::class);
        if (!empty($attributes)) {
            $repositoryClass = $attributes[0]->newInstance()->repositoryClass;
            if ($repositoryClass) {
                return $repositoryClass;
            }
        }

        // Return the default repository class configured.
        $repositoryClass = $this->getConfiguration()->get('repositoryClass');

        // Throw error if the class doesn't exist as it might have been
        // misspelled by the programmer.
        if (!class_exists($repositoryClass)) {
            throw new ManagerException(sprintf(
                'Repository class %s does not exist. Could it be misspelled?',
                $repositoryClass
            ));
        }

        // Return repository class.
        return $repositoryClass;
    }
}
