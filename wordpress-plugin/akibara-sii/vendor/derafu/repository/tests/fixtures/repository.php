<?php

declare(strict_types=1);

/**
 * Derafu: Repository - Lightweight File Data Source Management for PHP.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Order;

return [
    'products' => [
        // Los datos.
        // El 'id' es obligatorio en cada una de las entidades, se agregará
        // automáticamente en el test.
        'data' => [
            'prod-001' => [
                'name' => 'Laptop XPS',
                'category' => 'computers',
                'price' => 1299.99,
                'active' => true,
            ],
            'prod-002' => [
                'name' => 'Magic Mouse',
                'category' => 'accessories',
                'price' => 99.99,
                'active' => true,
            ],
            'prod-003' => [
                'name' => 'Old Keyboard',
                'category' => 'accessories',
                'price' => 29.99,
                'active' => false,
            ],
            'prod-004' => [
                'name' => 'Gaming Mouse',
                'category' => 'accessories',
                'price' => 149.99,
                'active' => true,
            ],
        ],
        // Los casos de prueba.
        'cases' => [
            'find_by_id' => [
                'method' => 'find',
                'args' => ['prod-001'],
                'expected' => [
                    'id' => 'prod-001',
                    'name' => 'Laptop XPS',
                    'category' => 'computers',
                    'price' => 1299.99,
                    'active' => true,
                ],
            ],
            'find_nonexistent' => [
                'method' => 'find',
                'args' => ['prod-999'],
                'expected' => null,
            ],
            'find_all' => [
                'method' => 'findAll',
                'args' => [],
                'expected' => [
                    [
                        'id' => 'prod-001',
                        'name' => 'Laptop XPS',
                        'category' => 'computers',
                        'price' => 1299.99,
                        'active' => true,
                    ],
                    [
                        'id' => 'prod-002',
                        'name' => 'Magic Mouse',
                        'category' => 'accessories',
                        'price' => 99.99,
                        'active' => true,
                    ],
                    [
                        'id' => 'prod-003',
                        'name' => 'Old Keyboard',
                        'category' => 'accessories',
                        'price' => 29.99,
                        'active' => false,
                    ],
                    [
                        'id' => 'prod-004',
                        'name' => 'Gaming Mouse',
                        'category' => 'accessories',
                        'price' => 149.99,
                        'active' => true,
                    ],
                ],
            ],
            'find_active_accessories' => [
                'method' => 'findBy',
                'args' => [
                    ['category' => 'accessories', 'active' => true], // criteria
                    ['price' => 'DESC'],                             // orderBy
                    null,                                            // limit
                    null,                                            // offset
                ],
                'expected' => [
                    [
                        'id' => 'prod-004',
                        'name' => 'Gaming Mouse',
                        'category' => 'accessories',
                        'price' => 149.99,
                        'active' => true,
                    ],
                    [
                        'id' => 'prod-002',
                        'name' => 'Magic Mouse',
                        'category' => 'accessories',
                        'price' => 99.99,
                        'active' => true,
                    ],
                ],
            ],
            'find_with_limit_offset' => [
                'method' => 'findBy',
                'args' => [
                    ['category' => 'accessories'], // criteria
                    ['price' => 'ASC'],            // orderBy
                    2,                             // limit
                    1,                             // offset
                ],
                'expected' => [
                    [
                        'id' => 'prod-002',
                        'name' => 'Magic Mouse',
                        'category' => 'accessories',
                        'price' => 99.99,
                        'active' => true,
                    ],
                    [
                        'id' => 'prod-004',
                        'name' => 'Gaming Mouse',
                        'category' => 'accessories',
                        'price' => 149.99,
                        'active' => true,
                    ],
                ],
            ],
            'find_one_by' => [
                'method' => 'findOneBy',
                'args' => [
                    ['category' => 'computers'], // criteria
                    null,                        // orderBy
                ],
                'expected' => [
                    'id' => 'prod-001',
                    'name' => 'Laptop XPS',
                    'category' => 'computers',
                    'price' => 1299.99,
                    'active' => true,
                ],
            ],
            'find_one_by_no_results' => [
                'method' => 'findOneBy',
                'args' => [
                    ['category' => 'phones'], // criteria que no existe
                    null,                     // orderBy
                ],
                'expected' => null,
            ],
            'count_all' => [
                'method' => 'count',
                'args' => [],
                'expected' => 4,
            ],
            'criteria' => [
                'method' => 'findByCriteria',
                'args' => [
                    Criteria::create()
                        ->where(Criteria::expr()->lte('price', 100))
                    ,
                ],
                'expected' => [
                    [
                        'id' => 'prod-002',
                        'name' => 'Magic Mouse',
                        'category' => 'accessories',
                        'price' => 99.99,
                        'active' => true,
                    ],
                    [
                        'id' => 'prod-003',
                        'name' => 'Old Keyboard',
                        'category' => 'accessories',
                        'price' => 29.99,
                        'active' => false,
                    ],
                ],
            ],
        ],
    ],
    // Pruebas de límites y offsets
    'find_with_zero_limit' => [
        // Los datos.
        'data' => [
            'item1' => ['id' => 1, 'name' => 'Test 1', 'value' => 10],
            'item2' => ['id' => 2, 'name' => 'Test 2', 'value' => 20],
        ],
        // Los casos de prueba.
        'cases' => [
            'findBy' => [
                'method' => 'findBy',
                'args' => [
                    [], // criteria
                    null, // orderBy
                    0, // limit
                    null, // offset
                ],
                'expected' => [],
            ],
        ],
    ],
    'find_with_large_offset' => [
        // Los datos.
        'data' => [
            'item1' => ['id' => 1, 'name' => 'Test 1', 'value' => 10],
            'item2' => ['id' => 2, 'name' => 'Test 2', 'value' => 20],
        ],
        // Los casos de prueba.
        'cases' => [
            'findBy' => [
                'method' => 'findBy',
                'args' => [
                    [], // criteria
                    null, // orderBy
                    null, // limit
                    10, // offset
                ],
                'expected' => [],
            ],
        ],
    ],
    // Pruebas de búsqueda con múltiples valores
    'find_with_multiple_values' => [
        // Los datos.
        'data' => [
            'item1' => ['id' => 1, 'name' => 'Test 1', 'category' => 'A'],
            'item2' => ['id' => 2, 'name' => 'Test 2', 'category' => 'B'],
            'item3' => ['id' => 3, 'name' => 'Test 3', 'category' => 'A'],
        ],
        // Los casos de prueba.
        'cases' => [
            'findBy' => [
                'method' => 'findBy',
                'args' => [
                    ['category' => ['A', 'B']], // criteria with multiple values
                    null, // orderBy
                    null, // limit
                    null, // offset
                ],
                'expected' => [
                    [
                        'id' => 1,
                        'name' => 'Test 1',
                        'category' => 'A',
                    ],
                    [
                        'id' => 2,
                        'name' => 'Test 2',
                        'category' => 'B',
                    ],
                    [
                        'id' => 3,
                        'name' => 'Test 3',
                        'category' => 'A',
                    ],
                ],
            ],
        ],
    ],
    // Pruebas de ordenamiento complejo
    'find_with_complex_ordering' => [
        // Los datos.
        'data' => [
            'item1' => ['id' => 1, 'name' => 'Test 1', 'value' => 10, 'priority' => 2],
            'item2' => ['id' => 2, 'name' => 'Test 2', 'value' => 20, 'priority' => 1],
            'item3' => ['id' => 3, 'name' => 'Test 3', 'value' => 15, 'priority' => 2],
        ],
        // Los casos de prueba.
        'cases' => [
            'findBy' => [
                'method' => 'findBy',
                'args' => [
                    [], // criteria
                    ['priority' => 'ASC', 'value' => 'DESC'], // orderBy
                    null, // limit
                    null, // offset
                ],
                'expected' => [
                    [
                        'id' => 2,
                        'name' => 'Test 2',
                        'value' => 20,
                        'priority' => 1,
                    ],
                    [
                        'id' => 3,
                        'name' => 'Test 3',
                        'value' => 15,
                        'priority' => 2,
                    ],
                    [
                        'id' => 1,
                        'name' => 'Test 1',
                        'value' => 10,
                        'priority' => 2,
                    ],
                ],
            ],
        ],
    ],
    // Prueba de criterios complejos con Doctrine Criteria
    'find_with_doctrine_criteria' => [
        // Los datos.
        'data' => [
            'item1' => ['id' => 1, 'name' => 'Test 1', 'value' => 10],
            'item2' => ['id' => 2, 'name' => 'Test 2', 'value' => 20],
            'item3' => ['id' => 3, 'name' => 'Test 3', 'value' => 15],
        ],
        // Los casos de prueba.
        'cases' => [
            'findByCriteria' => [
                'method' => 'findByCriteria',
                'args' => [
                    Criteria::create()
                        ->where(Criteria::expr()->gt('value', 12))
                        ->orderBy(['value' => Order::Ascending]),
                ],
                'expected' => [
                    [
                        'id' => 3,
                        'name' => 'Test 3',
                        'value' => 15,
                    ],
                    [
                        'id' => 2,
                        'name' => 'Test 2',
                        'value' => 20,
                    ],
                ],
            ],
        ],
    ],
    // Prueba de búsqueda con ID inválido
    'find_with_invalid_id_type' => [
        // Los datos.
        'data' => [
            'item1' => ['id' => 1, 'name' => 'Test 1'],
        ],
        // Los casos de prueba.
        'cases' => [
            'find' => [
                'method' => 'find',
                'args' => [['invalid' => 'type']],
                'expectedException' => InvalidArgumentException::class,
            ],
        ],
    ],
    // Prueba de límite y offset inválidos
    'find_with_negative_limit' => [
        // Los datos.
        'data' => [
            'item1' => ['id' => 1, 'name' => 'Test 1'],
        ],
        // Los casos de prueba.
        'cases' => [
            'findBy' => [
                'method' => 'findBy',
                'args' => [
                    [], // criteria
                    null, // orderBy
                    -1, // invalid limit
                    null, // offset
                ],
                'expectedException' => InvalidArgumentException::class,
            ],
        ],
    ],
];
