<?php

declare(strict_types=1);

/**
 * Derafu: Config - Yet Another Config Lib.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Config;

use Derafu\Config\Contract\OptionsInterface;
use Derafu\Container\Vault;

class Options extends Vault implements OptionsInterface
{
    /**
     * {@inheritDoc}
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $value = parent::get($key, $default);

        if (is_array($value)) {
            return new self($value);
        }

        return $value;
    }
}
