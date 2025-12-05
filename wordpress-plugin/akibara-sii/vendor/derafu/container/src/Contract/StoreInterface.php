<?php

declare(strict_types=1);

/**
 * Derafu: Container - Flexible Data Containers for PHP.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Container\Contract;

use Exception;

/**
 * Interfaz para contenedor de datos estructurados en JSON con schema.
 */
interface StoreInterface extends ContainerInterface
{
    /**
     * Asigna el schema que se usará para validar los datos.
     *
     * @param array $schema Nuevo schema a utilizar.
     * @return static Permite encadenar métodos.
     */
    public function setSchema(array $schema): static;

    /**
     * Obtiene el schema de datos definido.
     *
     * @return array Schema actual.
     */
    public function getSchema(): array;

    /**
     * Valida que los datos almacenados cumplan con el schema.
     *
     * @return void
     * @throws Exception Lanzará una excepción si ocurre algún error.
     */
    public function validate(): void;
}
