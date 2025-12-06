<?php

declare(strict_types=1);

/**
 * Derafu: Container - Flexible Data Containers for PHP.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

return [
    'without_validation_because_empty_schema' => [
        'data' => [
            'foo' => 'bar',
        ],
        'schema' => [
            'type' => 'object',
            'additionalProperties' => true,
        ],
        'expected' => [
            'foo' => 'bar',
        ],
    ],
    'basic_schema_validation_ok' => [
        'data' => [
            'name' => 'John',
        ],
        'schema' => [
            'type' => 'object',
            'required' => ['name'],
            'properties' => [
                'name' => [
                    'type' => 'string',
                ],
                'age' => [
                    'type' => 'integer',
                    'default' => 18,
                ],
            ],
        ],
        'expected' => [
            'name' => 'John',
            'age' => 18,
        ],
    ],
    'basic_schema_validation_fail' => [
        'data' => [
            // Vacío a propósito para que falle
        ],
        'schema' => [
            'type' => 'object',
            'required' => ['email'],
            'properties' => [
                'email' => [
                    'type' => 'string',
                ],
            ],
        ],
        'expected' => InvalidArgumentException::class,
    ],
    'choices_validation_ok' => [
        'data' => [
            'status' => 'active',
        ],
        'schema' => [
            'type' => 'object',
            'required' => ['status'],
            'properties' => [
                'status' => [
                    'type' => 'string',
                    'enum' => ['active', 'inactive'],
                ],
            ],
        ],
        'expected' => [
            'status' => 'active',
        ],
    ],
    'choices_validation_fail' => [
        'data' => [
            'status' => 'invalid',
        ],
        'schema' => [
            'type' => 'object',
            'required' => ['status'],
            'properties' => [
                'status' => [
                    'type' => 'string',
                    'enum' => ['active', 'inactive'],
                ],
            ],
        ],
        'expected' => InvalidArgumentException::class,
    ],
    'type_validation_fail' => [
        'data' => [
            'age' => 'not_a_number',
        ],
        'schema' => [
            'type' => 'object',
            'properties' => [
                'age' => [
                    'type' => 'integer',
                ],
            ],
        ],
        'expected' => InvalidArgumentException::class,
    ],
    'format_validation_fail' => [
        'data' => [
            'email' => 'not_an_email',
        ],
        'schema' => [
            'type' => 'object',
            'properties' => [
                'email' => [
                    'type' => 'string',
                    'format' => 'email',
                ],
            ],
        ],
        'expected' => InvalidArgumentException::class,
    ],
    'number_constraint_fail' => [
        'data' => [
            'price' => -1,
        ],
        'schema' => [
            'type' => 'object',
            'properties' => [
                'price' => [
                    'type' => 'number',
                    'minimum' => 0,
                    'exclusiveMinimum' => true,
                ],
            ],
        ],
        'expected' => InvalidArgumentException::class,
    ],
    'nested_schema_validation' => [
        'data' => [
            'user' => [
                'name' => 'John',
            ],
        ],
        'schema' => [
            'type' => 'object',
            'properties' => [
                'user' => [
                    'type' => 'object',
                    'required' => ['name'],
                    'properties' => [
                        'name' => [
                            'type' => 'string',
                        ],
                        'email' => [
                            'type' => 'string',
                            'default' => 'default@example.com',
                        ],
                    ],
                ],
            ],
        ],
        'expected' => [
            'user.name' => 'John',
            'user.email' => 'default@example.com',
        ],
    ],
    'complex_nested_validation' => [
        'data' => [
            'user' => [
                'profile' => [
                    'name' => 'John',
                ],
            ],
        ],
        'schema' => [
            'type' => 'object',
            'properties' => [
                'user' => [
                    'type' => 'object',
                    'properties' => [
                        'profile' => [
                            'type' => 'object',
                            'required' => ['name'],
                            'properties' => [
                                'name' => [
                                    'type' => 'string',
                                ],
                                'age' => [
                                    'type' => 'integer',
                                    'default' => 18,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
        'expected' => [
            'user.profile.age' => 18,
        ],
    ],
    'combined_features_validation' => [
        'data' => [
            'order' => [
                'items' => [
                    'price' => 99.99,
                    'status' => 'active',
                ],
                'customer' => [
                    'name' => 'John',
                ],
            ],
        ],
        'schema' => [
            'type' => 'object',
            'properties' => [
                'order' => [
                    'type' => 'object',
                    'properties' => [
                        'items' => [
                            'type' => 'object',
                            'required' => ['price', 'status'],
                            'properties' => [
                                'price' => [
                                    'type' => 'number',
                                    'minimum' => 0,
                                    'exclusiveMinimum' => true,
                                ],
                                'status' => [
                                    'type' => 'string',
                                    'enum' => ['active', 'inactive'],
                                ],
                            ],
                        ],
                        'customer' => [
                            'type' => 'object',
                            'required' => ['name'],
                            'properties' => [
                                'name' => [
                                    'type' => 'string',
                                ],
                                'email' => [
                                    'type' => 'string',
                                    'format' => 'email',
                                    'default' => 'no@email.com',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
        'expected' => [
            'order.items.price' => 99.99,
            'order.items.status' => 'active',
            'order.customer.name' => 'John',
            'order.customer.email' => 'no@email.com',
        ],
    ],
];
