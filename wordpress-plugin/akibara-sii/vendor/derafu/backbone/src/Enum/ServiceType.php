<?php

declare(strict_types=1);

/**
 * Derafu: Backbone - The Architectural Spine for PHP Libraries.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Backbone\Enum;

use Derafu\Backbone\Attribute\Component;
use Derafu\Backbone\Attribute\Handler;
use Derafu\Backbone\Attribute\Job;
use Derafu\Backbone\Attribute\Package;
use Derafu\Backbone\Attribute\Strategy;
use Derafu\Backbone\Attribute\Worker;

/**
 * Enum for the types of services.
 */
enum ServiceType: string
{
    case PACKAGE = 'package';
    case COMPONENT = 'component';
    case WORKER = 'worker';
    case JOB = 'job';
    case HANDLER = 'handler';
    case STRATEGY = 'strategy';

    /**
     * Gets the attribute class for the service type.
     *
     * @return string
     */
    public function getAttributeClass(): string
    {
        return match ($this) {
            self::PACKAGE => Package::class,
            self::COMPONENT => Component::class,
            self::WORKER => Worker::class,
            self::JOB => Job::class,
            self::HANDLER => Handler::class,
            self::STRATEGY => Strategy::class,
        };
    }
}
