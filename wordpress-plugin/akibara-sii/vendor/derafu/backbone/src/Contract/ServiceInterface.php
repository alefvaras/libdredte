<?php

declare(strict_types=1);

/**
 * Derafu: Backbone - The Architectural Spine for PHP Libraries.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Backbone\Contract;

use Derafu\Config\Contract\OptionsAwareInterface;
use Stringable;

/**
 * Base interface for the services of the application.
 */
interface ServiceInterface extends Stringable, OptionsAwareInterface
{
    /**
     * Gets the unique identifier of the class.
     *
     * @return int|string
     */
    public function getId(): int|string;

    /**
     * Gets the descriptive name of the class.
     *
     * @return string
     */
    public function getName(): string;
}
