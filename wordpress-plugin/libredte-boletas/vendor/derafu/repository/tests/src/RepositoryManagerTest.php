<?php

declare(strict_types=1);

/**
 * Derafu: Repository - Lightweight File Data Source Management for PHP.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\TestsRepository;

use Derafu\Repository\Contract\DataProviderInterface;
use Derafu\Repository\Contract\RepositoryInterface;
use Derafu\Repository\Entity;
use Derafu\Repository\Exception\ManagerException;
use Derafu\Repository\Repository;
use Derafu\Repository\Service\RepositoryManager;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(RepositoryManager::class)]
#[CoversClass(Repository::class)]
#[CoversClass(Entity::class)]
#[CoversClass(\Derafu\Repository\Service\DataProvider::class)]
class RepositoryManagerTest extends TestCase
{
    public static function provideTestCases(): array
    {
        $tests = require dirname(__DIR__) . '/fixtures/repository-manager.php';

        $testCases = [];

        foreach ($tests as $name => $test) {
            foreach ($test['cases'] as $caseName => $case) {
                $testCases[$name . ':' . $caseName] = [
                    $test['sources'] ?? [],
                    $case,
                ];
            }
        }

        return $testCases;
    }

    #[DataProvider('provideTestCases')]
    public function testRepositoryManagerCase(array $sources, array $case): void
    {
        $dataProvider = $this->createDataProvider($sources);
        $manager = new RepositoryManager($dataProvider);

        $expectedException = $case['expectedException'] ?? null;

        if ($expectedException !== null) {
            $this->expectException($expectedException);
        }

        if (isset($case['repositoryId'])) {
            $this->testSingleRepository($manager, $case);
        } elseif (isset($case['repositoryIds'])) {
            $this->testMultipleRepositories($manager, $case);
        }
    }

    private function testSingleRepository(RepositoryManager $manager, array $case): void
    {
        $repositoryId = $case['repositoryId'];
        $repository = $manager->getRepository($repositoryId);

        // Test that we get a valid repository
        $this->assertInstanceOf(RepositoryInterface::class, $repository);
        $this->assertInstanceOf(Repository::class, $repository);

        // Test entity class resolution
        if (isset($case['expectedEntityClass'])) {
            $this->assertSame($case['expectedEntityClass'], $repository->getClassName());
        }

        // Test repository functionality
        if (isset($case['expectedCount'])) {
            $this->assertSame($case['expectedCount'], $repository->count());
        }

        // Test caching behavior
        if (isset($case['shouldBeCached']) && $case['shouldBeCached']) {
            $repository2 = $manager->getRepository($repositoryId);
            $this->assertSame($repository, $repository2, 'Repository should be cached');
        }
    }

    private function testMultipleRepositories(RepositoryManager $manager, array $case): void
    {
        $repositoryIds = $case['repositoryIds'];
        $expectedCounts = $case['expectedCounts'];

        $repositories = [];
        foreach ($repositoryIds as $index => $repositoryId) {
            $repositories[] = $manager->getRepository($repositoryId);
            $this->assertInstanceOf(RepositoryInterface::class, $repositories[$index]);
            $this->assertSame($expectedCounts[$index], $repositories[$index]->count());
        }
    }

    private function createDataProvider(array $sources): DataProviderInterface
    {
        // Convert array sources to file paths
        $fileSources = [];
        foreach ($sources as $sourceId => $data) {
            $filePath = dirname(__DIR__) . '/fixtures/' . $sourceId . '.php';
            $fileSources[$sourceId] = $filePath;
        }

        $dataProvider = new \Derafu\Repository\Service\DataProvider($fileSources);

        // Set default configuration to avoid normalization issues
        $dataProvider->setConfiguration([
            'normalization' => [
                'idAttribute' => 'id',
                'nameAttribute' => 'name',
            ],
        ]);

        return $dataProvider;
    }

    /**
     * Test basic repository manager functionality.
     */
    public function testBasicRepositoryManager(): void
    {
        $sources = [
            'products' => 'products',
        ];

        $dataProvider = $this->createDataProvider($sources);
        $manager = new RepositoryManager($dataProvider);

        $repository = $manager->getRepository('products');

        $this->assertInstanceOf(Repository::class, $repository);
        $this->assertSame(4, $repository->count());
        $this->assertSame(Entity::class, $repository->getClassName());
    }

    /**
     * Test repository manager with entity class resolution.
     */
    public function testEntityClassResolution(): void
    {
        $sources = [
            'custom_entity' => 'custom_entity',
        ];

        $dataProvider = $this->createDataProvider($sources);
        $manager = new RepositoryManager($dataProvider);

        // Test with string identifier (should use default entity class)
        $repository = $manager->getRepository('custom_entity');
        $this->assertSame(Entity::class, $repository->getClassName());
    }

    /**
     * Test repository manager error handling.
     */
    public function testErrorHandling(): void
    {
        $dataProvider = $this->createDataProvider([]);
        $manager = new RepositoryManager($dataProvider);

        // Test non-existent source
        $this->expectException(ManagerException::class);
        $manager->getRepository('non_existent');
    }

    /**
     * Test repository manager with non-existent entity class.
     */
    public function testNonExistentEntityClass(): void
    {
        $sources = [
            'products' => 'products',
        ];

        $dataProvider = $this->createDataProvider($sources);
        $manager = new RepositoryManager($dataProvider);

        $this->expectException(ManagerException::class);
        $manager->getRepository('NonExistent\Entity\Class');
    }

    /**
     * Test repository manager caching behavior.
     */
    public function testRepositoryCaching(): void
    {
        $sources = [
            'products' => 'products',
        ];

        $dataProvider = $this->createDataProvider($sources);
        $manager = new RepositoryManager($dataProvider);

        $repository1 = $manager->getRepository('products');
        $repository2 = $manager->getRepository('products');

        $this->assertSame($repository1, $repository2, 'Repository should be cached');
        $this->assertSame(4, $repository1->count());
    }

    /**
     * Test repository manager with multiple repositories.
     */
    public function testMultipleRepositoriesIntegration(): void
    {
        $sources = [
            'products' => 'products',
            'users' => 'users',
        ];

        $dataProvider = $this->createDataProvider($sources);
        $manager = new RepositoryManager($dataProvider);

        $productsRepo = $manager->getRepository('products');
        $usersRepo = $manager->getRepository('users');

        $this->assertSame(4, $productsRepo->count());
        $this->assertSame(3, $usersRepo->count());
        $this->assertNotSame($productsRepo, $usersRepo);
    }

    /**
     * Test repository manager with repository operations.
     */
    public function testRepositoryOperations(): void
    {
        $sources = [
            'products' => 'products',
        ];

        $dataProvider = $this->createDataProvider($sources);
        $manager = new RepositoryManager($dataProvider);

        $repository = $manager->getRepository('products');

        // Test find
        $item = $repository->find('prod-001');
        $this->assertNotNull($item);
        $this->assertSame('Laptop XPS', $item->name);

        // Test findAll
        $allItems = $repository->findAll();
        $this->assertCount(4, $allItems);

        // Test findBy
        $activeItems = $repository->findBy(['active' => true]);
        $this->assertCount(3, $activeItems);

        // Test findOneBy
        $computerItem = $repository->findOneBy(['category' => 'computers']);
        $this->assertNotNull($computerItem);
        $this->assertSame('computers', $computerItem->category);

        // Test count
        $this->assertSame(4, $repository->count());
        $this->assertSame(3, $repository->count(['active' => true]));
    }
}
