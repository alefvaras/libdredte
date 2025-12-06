<?php

declare(strict_types=1);

/**
 * Derafu: Kernel - Lightweight Kernel Implementation with Container.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $configurator) {
    $services = $configurator->services();

    // Service that uses environment variables.
    $services->set('database.connection', stdClass::class)
        ->args([
            '%env(DATABASE_HOST)%',
            '%env(DATABASE_USER)%',
            '%env(DATABASE_PASSWORD)%',
        ])
        ->public();

    // Service that uses environment variables with defaults.
    $services->set('api.client', stdClass::class)
        ->args([
            '%env(API_KEY)%',
            '%env(API_URL)%',
        ])
        ->public();
};
