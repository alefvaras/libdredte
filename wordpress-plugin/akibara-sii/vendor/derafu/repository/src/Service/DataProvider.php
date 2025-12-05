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

use ArrayObject;
use Derafu\Config\Trait\ConfigurableTrait;
use Derafu\Repository\Contract\DataProviderInterface;
use Derafu\Repository\Exception\DataProviderException;
use Derafu\Support\Arr;
use Psr\SimpleCache\CacheInterface;
use Symfony\Component\Yaml\Yaml;

/**
 * Data provider.
 */
class DataProvider implements DataProviderInterface
{
    use ConfigurableTrait;

    /**
     * Worker configuration schema.
     *
     * @var array
     */
    protected array $configurationSchema = [
        'normalization' => [
            'types' => 'array',
            'default' => [],
            'schema' => [
                'idAttribute' => [
                    'types' => 'string',
                    'default' => 'id',
                ],
                'nameAttribute' => [
                    'types' => 'string',
                    'default' => 'name',
                ],
            ],
        ],
    ];

    /**
     * List of entity repository data sources.
     *
     * It's a map that contains in the index the entity class associated with
     * the source and in the value the source.
     *
     * If the index is not a valid class it will be mapped to a default
     * standard entity class.
     *
     * The entity can provide a custom repository for its management.
     *
     * The same source (value) can be in different entities (index).
     *
     * @var array<string, string>
     */
    private array $sources;

    /**
     * Instance to access a cache to search for data.
     *
     * @var CacheInterface|null
     */
    private ?CacheInterface $cache;

    /**
     * In-memory data sources that have already had their data loaded.
     *
     * @var array<string,ArrayObject>
     */
    private array $loaded;

    /**
     * Worker constructor.
     *
     * @param array<string,string> $sources Data sources (ID and source).
     * @param CacheInterface|null $cache Cache instance.
     * @param array $config Configuration.
     */
    public function __construct(
        array $sources = [],
        ?CacheInterface $cache = null,
        array $config = []
    ) {
        $this->sources = $sources;
        $this->cache = $cache;
        if (!empty($config)) {
            $this->setConfiguration($config);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function fetch(string $source): ArrayObject
    {
        // If the source is not loaded it's loaded.
        if (!isset($this->loaded[$source])) {
            $data = $this->fetchData($source);

            $normalizationConfig = $this->getConfiguration()->get(
                'normalization'
            )->all();
            $data = $this->normalizeData($data, $normalizationConfig);

            $this->loaded[$source] = new ArrayObject($data);
        }

        // Return loaded data from the source.
        return $this->loaded[$source];
    }

    /**
     * Centralizes data loading for a source.
     *
     * This allows loading data from a cache, files or in the future other
     * sources where data might be located.
     *
     * @param string $source
     * @return array
     */
    private function fetchData(string $source): array
    {
        // Load source data from a cache.
        $data = $this->fetchDataFromCacheSource($source);
        if ($data !== null) {
            return $data;
        }

        // If there's no data source for the source an error is generated.
        if (!isset($this->sources[$source])) {
            throw new DataProviderException(sprintf(
                'No data source configured for %s.',
                $source
            ));
        }

        // Load source data from a file.
        $data = $this->fetchDataFromFileSource($source);

        // Save data in cache.
        if (isset($this->cache)) {
            $key = $this->createCacheKey($source);
            $this->cache->set($key, $data);
        }

        // Return found data.
        return $data;
    }

    /**
     * Loads source data from a cache (if available).
     *
     * @param string $source
     * @return array|null
     */
    private function fetchDataFromCacheSource(string $source): ?array
    {
        $key = $this->createCacheKey($source);

        if (isset($this->cache) && $this->cache->has($key)) {
            return $this->cache->get($key);
        }

        return null;
    }

    /**
     * Loads source data from a file.
     *
     * The file can be: .php, .json or .yaml
     *
     * @param string $source
     * @return array
     */
    private function fetchDataFromFileSource(string $source): array
    {
        $filepath = $this->sources[$source];

        $extension = strtolower(pathinfo($filepath, PATHINFO_EXTENSION));

        switch ($extension) {
            case 'php':
                $data = require $filepath;
                break;
            case 'json':
                $data = json_decode(file_get_contents($filepath), true);
                break;
            case 'yaml':
                $data = Yaml::parseFile(file_get_contents($filepath));
                break;
            default:
                $data = $this->handleExtension($source, $filepath, $extension);
        }

        if (!is_array($data)) {
            throw new DataProviderException(sprintf(
                'Data from source %s is not valid to be used as a data source. Path: %s.',
                $source,
                $filepath
            ));
        }

        return $data;
    }

    /**
     * Handles the case when a file extension is not supported.
     *
     * Works as a "hook" to customize behavior through inheritance.
     *
     * @param string $source
     * @param string $filepath
     * @param string $extension
     * @return array
     */
    protected function handleExtension(
        string $source,
        string $filepath,
        string $extension
    ): array {
        throw new DataProviderException(sprintf(
            'File format %s from data source %s is not supported. Path: %s.',
            $extension,
            $source,
            $filepath
        ));
    }

    /**
     * Normalizes data in case it's an array of values and not an array of
     * arrays.
     *
     * @param array $data
     * @return array<int|string, array>
     */
    private function normalizeData(array $data, array $config): array
    {
        $nameAttribute = $config['nameAttribute'];

        $data = array_map(function ($entity) use ($nameAttribute) {
            if (!is_array($entity)) {
                return [
                    $nameAttribute => $entity,
                ];
            }
            return $entity;
        }, $data);

        return Arr::ensureIdInElements($data, $config['idAttribute']);
    }

    /**
     * Creates the key from the data source identifier.
     *
     * @param string $source Data source identifier.
     * @return string Key to use with the cache.
     */
    private function createCacheKey(string $source): string
    {
        return 'derafu:repository:data_provider:' . $source;
    }
}
