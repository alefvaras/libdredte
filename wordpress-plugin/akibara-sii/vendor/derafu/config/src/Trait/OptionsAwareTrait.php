<?php

declare(strict_types=1);

/**
 * Derafu: Config - Yet Another Config Lib.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Config\Trait;

use Derafu\Config\Contract\OptionsInterface;
use Derafu\Config\Options;

/**
 * Trait para clases que deseen implementar un sistema simple de opciones.
 *
 * @see Derafu\Config\Contract\OptionsAwareInterface
 */
trait OptionsAwareTrait
{
    /**
     * Contenedor para las opciones.
     *
     * @var OptionsInterface
     */
    protected OptionsInterface $options;

    /**
     * {@inheritDoc}
     */
    public function setOptions(array|OptionsInterface $options): static
    {
        if (is_array($options)) {
            $options = new Options($options, $this->getOptionsSchema());
        }

        $this->options = $options;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getOptions(): OptionsInterface
    {
        if (!isset($this->options)) {
            $this->setOptions([]);
        }

        $this->options->validate();

        return $this->options;
    }

    /**
     * {@inheritDoc}
     */
    public function resolveOptions(array $options = []): OptionsInterface
    {
        if (isset($this->options)) {
            $options = array_merge($this->options->all(), $options);
        }

        return $this->setOptions($options)->options;
    }

    /**
     * {@inheritDoc}
     */
    public function getOptionsSchema(): array
    {
        return $this->optionsSchema ?? [];
    }
}
