<?php

declare(strict_types=1);

/**
 * Derafu: Backbone - The Architectural Spine for PHP Libraries.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Backbone\Abstract;

use Derafu\Backbone\Contract\HandlerInterface;
use Derafu\Backbone\Trait\JobsAwareTrait;
use Derafu\Backbone\Trait\StrategiesAwareTrait;

/**
 * Base class for the handlers of the workers of the application.
 */
abstract class AbstractHandler extends AbstractService implements HandlerInterface
{
    use JobsAwareTrait;
    use StrategiesAwareTrait;
}
