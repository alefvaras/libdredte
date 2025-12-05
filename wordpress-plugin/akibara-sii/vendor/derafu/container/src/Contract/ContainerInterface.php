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
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use JsonSerializable;

/**
 * Interfaz base para todos los almacenamientos.
 */
interface ContainerInterface extends ArrayAccess, JsonSerializable
{
    /**
     * Obtiene la colección de los datos almacenados.
     *
     * @return ArrayCollection Colección con todos los valores almacenados.
     */
    public function collection(): ArrayCollection;

    /**
     * Obtiene todos los valores almacenados.
     *
     * @return array Arreglo con todos los valores almacenados.
     */
    public function all(): array;

    /**
     * Asigna un valor a una llave.
     *
     * @param string $key Llave donde se almacenará el valor.
     * @param mixed $value Valor que se desea almacenar.
     * @return static Permite encadenar métodos.
     */
    public function set(string $key, mixed $value): static;

    /**
     * Obtiene un valor almacenado.
     *
     * @param string $key Llave del valor que se desea obtener.
     * @param mixed $default Valor por defecto si la llave no existe.
     * @return mixed Valor almacenado o valor por defecto.
     */
    public function get(string $key, mixed $default = null): mixed;

    /**
     * Verifica si existe un valor para una llave.
     *
     * @param string $key Llave que se desea verificar.
     * @return bool True si la llave existe, false en caso contrario.
     */
    public function has(string $key): bool;

    /**
     * Elimina todos los valores almacenados o uno en particular.
     *
     * @param string|null $key Llave que se desea eliminar.
     */
    public function clear(?string $key = null): void;

    /**
     * Aplica un criterio para filtrar los elementos almacenados.
     *
     * Este método permite filtrar y ordenar los elementos en la colección de
     * acuerdo a las condiciones definidas en un objeto `Criteria`.
     *
     * El resultado es una nueva colección (`ArrayCollection`) que contiene
     * únicamente los elementos que cumplen con las condiciones.
     *
     * @param Criteria $criteria El objeto `Criteria` que define las
     * condiciones, el orden y los límites de los resultados.
     * @return ArrayCollection Una nueva colección con los elementos que cumplen
     * el criterio especificado.
     * @see \Doctrine\Common\Collections\Criteria
     * @see \Doctrine\Common\Collections\ArrayCollection
     */
    public function matching(Criteria $criteria): ArrayCollection;
}
