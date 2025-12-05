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

/**
 * Interfaz para almacenamiento secuencial de elementos.
 */
interface JournalInterface extends ContainerInterface
{
    /**
     * Agrega un elemento al final del journal.
     *
     * @param mixed $item Elemento a agregar
     * @return static Permite encadenar métodos.
     */
    public function add(mixed $item): static;

    /**
     * Obtiene los elementos en orden inverso (del más nuevo al más antiguo).
     *
     * @return array
     */
    public function reverse(): array;
}
