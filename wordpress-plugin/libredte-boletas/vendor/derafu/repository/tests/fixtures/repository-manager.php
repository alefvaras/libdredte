<?php

declare(strict_types=1);

/**
 * Derafu: Repository - Lightweight File Data Source Management for PHP.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

return [
    'basic_repository_loading' => [
        'sources' => [
            'products' => 'products',
        ],
        'cases' => [
            'get_repository_by_string_id' => [
                'repositoryId' => 'products',
                'expectedEntityClass' => 'Derafu\Repository\Entity',
                'expectedRepositoryClass' => 'Derafu\Repository\Repository',
                'expectedCount' => 4,
            ],
            'get_same_repository_twice_returns_cached' => [
                'repositoryId' => 'products',
                'expectedEntityClass' => 'Derafu\Repository\Entity',
                'expectedRepositoryClass' => 'Derafu\Repository\Repository',
                'expectedCount' => 4,
                'shouldBeCached' => true,
            ],
        ],
    ],
    'entity_class_resolution' => [
        'sources' => [
            'custom_entity' => 'custom_entity',
        ],
        'cases' => [
            'resolve_entity_class_from_fqcn' => [
                'repositoryId' => 'custom_entity',
                'expectedEntityClass' => 'Derafu\Repository\Entity',
                'expectedRepositoryClass' => 'Derafu\Repository\Repository',
            ],
            'resolve_entity_class_from_interface' => [
                'repositoryId' => 'custom_entity',
                'expectedEntityClass' => 'Derafu\Repository\Entity',
                'expectedRepositoryClass' => 'Derafu\Repository\Repository',
            ],
        ],
    ],
    'error_cases' => [
        'sources' => [],
        'cases' => [
            'non_existent_source_throws_exception' => [
                'repositoryId' => 'non_existent',
                'expectedException' => 'Derafu\Repository\Exception\ManagerException',
            ],
            'non_existent_entity_class_throws_exception' => [
                'repositoryId' => 'NonExistent\Entity\Class',
                'expectedException' => 'Derafu\Repository\Exception\ManagerException',
            ],
        ],
    ],
    'multiple_repositories' => [
        'sources' => [
            'products' => 'products',
            'users' => 'users',
        ],
        'cases' => [
            'load_multiple_repositories' => [
                'repositoryIds' => ['products', 'users'],
                'expectedCounts' => [4, 3],
            ],
        ],
    ],
];
