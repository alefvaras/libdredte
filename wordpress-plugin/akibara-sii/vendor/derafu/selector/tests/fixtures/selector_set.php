<?php

declare(strict_types=1);

/**
 * Derafu: Selector - Elegant Data Structure Navigation for PHP.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

return [

    // Caso #1: simple con arreglo inicial vacío.
    [
        'name' => 'Caso #1: simple con arreglo inicial vacío',
        'data' => [],
        'cases' => [
            [
                'selector' => 'new.key',
                'value' => 123,
                'expected' => ['new' => ['key' => 123]],
            ],
            [
                'selector' => 'new.key2',
                'value' => 'value',
                'expected' => ['new' => ['key' => 123, 'key2' => 'value']],
            ],
        ],
    ],

];
