<?php

declare(strict_types=1);

/**
 * Derafu: Config - Yet Another Config Lib.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Config\Contract;

/**
 * Interfaz para instancias que deseen implementar un sistema simple de
 * configuración.
 */
interface ConfigurableInterface
{
    /**
     * Asigna una configuración a la instancia.
     *
     * @param array|ConfigurationInterface $configuration
     * @return static
     */
    public function setConfiguration(
        array|ConfigurationInterface $configuration
    ): static;

    /**
     * Obtiene la configuración de la instancia.
     *
     * @return ConfigurationInterface
     */
    public function getConfiguration(): ConfigurationInterface;

    /**
     * Normaliza, y valida, la configuración de la instancia.
     *
     * @param array $configuration Configuración que puede estar sin normalizar.
     * @return ConfigurationInterface Configuración normalizadas y validadas.
     */
    public function resolveConfiguration(
        array $configuration
    ): ConfigurationInterface;

    /**
     * Entrega el esquema con el que se validarán las configuraciones.
     *
     * @return array<string,array<string,mixed>>
     */
    public function getConfigurationSchema(): array;
}
