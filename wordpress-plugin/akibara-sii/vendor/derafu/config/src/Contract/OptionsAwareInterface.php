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
 * Interfaz para instancias que deseen implementar un sistema simple de opciones.
 */
interface OptionsAwareInterface
{
    /**
     * Asigna las opciones que se deben usar en la instancia.
     *
     * Este método asigna nuevas opciones a la instancia.
     *
     * @param array|OptionsInterface $options
     * @return static
     */
    public function setOptions(array|OptionsInterface $options): static;

    /**
     * Obtiene las opciones que tiene asignada la instancia.
     *
     * @return OptionsInterface
     */
    public function getOptions(): OptionsInterface;

    /**
     * Normaliza, y valida, las opciones de la instancia.
     *
     * Este método resolverá las opciones haciendo merge con las que puedan
     * haber estado asignadas previamente y asignándolas a la instancia.
     *
     * @param array $options Opciones que pueden estar sin normalizar.
     * @return OptionsInterface Opciones normalizadas y validadas.
     */
    public function resolveOptions(array $options): OptionsInterface;

    /**
     * Entrega el esquema con el que se validarán las opciones.
     *
     * @return array<string,array<string,mixed>>
     */
    public function getOptionsSchema(): array;
}
