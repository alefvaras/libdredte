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

use Derafu\Config\Configuration;
use Derafu\Config\Contract\ConfigurationInterface;

/**
 * Trait para clases que deseen implementar un sistema simple de configuración.
 *
 * @see Derafu\Config\Contract\ConfigurableInterface
 */
trait ConfigurableTrait
{
    /**
     * Contenedor para las configuraciones.
     *
     * @var ConfigurationInterface
     */
    protected ConfigurationInterface $configuration;

    /**
     * {@inheritDoc}
     */
    public function setConfiguration(
        array|ConfigurationInterface $configuration
    ): static {
        if (is_array($configuration)) {
            $configuration = new Configuration(
                $configuration,
                $this->getConfigurationSchema()
            );
        }

        $this->configuration = $configuration;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getConfiguration(): ConfigurationInterface
    {
        if (!isset($this->configuration)) {
            $this->setConfiguration([]);
        }

        $this->configuration->validate();

        return $this->configuration;
    }

    /**
     * {@inheritDoc}
     */
    public function resolveConfiguration(
        array $configuration = []
    ): ConfigurationInterface {
        if (isset($this->configuration)) {
            $configuration = array_merge(
                $this->configuration->all(),
                $configuration
            );
        }

        return $this->setConfiguration($configuration)->configuration;
    }

    /**
     * {@inheritDoc}
     */
    public function getConfigurationSchema(): array
    {
        return $this->configurationSchema ?? [];
    }
}
