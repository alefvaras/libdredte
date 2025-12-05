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

use ArrayAccess;
use ArrayObject;

/**
 * Interfaz para contenedor simple de datos.
 */
interface BagInterface extends ContainerInterface
{
    /**
     * Reemplaza todos los valores almacenados por nuevos valores.
     *
     * @param array|ArrayAccess|ArrayObject $data Nuevos valores a almacenar.
     * @return static Permite encadenar métodos.
     */
    public function replace(array|ArrayAccess|ArrayObject $data): static;

    /**
     * Combina los valores almacenados con nuevos valores.
     *
     * @param array|ArrayAccess|ArrayObject $data Valores a combinar con los
     * existentes.
     * @return static Permite encadenar métodos.
     */
    public function merge(array|ArrayAccess|ArrayObject $data): static;

    /**
     * Elimina un valor almacenado.
     *
     * @param string $key Llave del valor que se desea eliminar.
     * @return static Permite encadenar métodos.
     */
    public function remove(string $key): static;
}
