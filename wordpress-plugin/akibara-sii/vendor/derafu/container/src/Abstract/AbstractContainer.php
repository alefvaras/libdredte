<?php

declare(strict_types=1);

/**
 * Derafu: Container - Flexible Data Containers for PHP.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Container\Abstract;

use ArrayAccess;
use ArrayObject;
use Derafu\Container\Contract\ContainerInterface;
use Derafu\Selector\Selector;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use InvalidArgumentException;
use Traversable;

/**
 * Clase base para todos los almacenamientos.
 */
abstract class AbstractContainer implements ContainerInterface
{
    /**
     * Colección de datos almacenados.
     *
     * @var ArrayCollection
     */
    protected ArrayCollection $data;

    /**
     * {@inheritDoc}
     */
    public function collection(): ArrayCollection
    {
        return $this->data;
    }

    /**
     * {@inheritDoc}
     */
    public function all(): array
    {
        return $this->toArray();
    }

    /**
     * {@inheritDoc}
     */
    public function set(string $key, mixed $value): static
    {
        $data = $this->toArray();
        Selector::set($data, $key, $value);
        $this->data = $this->createFrom($data);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return Selector::get($this->toArray(), $key, $default);
    }

    /**
     * {@inheritDoc}
     */
    public function has(string $key): bool
    {
        return Selector::has($this->toArray(), $key);
    }

    /**
     * {@inheritDoc}
     */
    public function clear(?string $key = null): void
    {
        if ($key === null) {
            $this->data = $this->createFrom([]);
        } else {
            $data = $this->toArray();
            Selector::clear($data, $key);
            $this->data = $this->createFrom($data);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function matching(Criteria $criteria): ArrayCollection
    {
        return $this->createFrom($this->data->matching($criteria)->toArray());
    }

    /**
     * {@inheritDoc}
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->set((string) $offset, $value);
    }

    /**
     * {@inheritDoc}
     */
    public function offsetGet(mixed $offset): mixed
    {
        return $this->get((string) $offset);
    }

    /**
     * {@inheritDoc}
     */
    public function offsetExists(mixed $offset): bool
    {
        return $this->has((string) $offset);
    }

    /**
     * {@inheritDoc}
     */
    public function offsetUnset(mixed $offset): void
    {
        $this->clear((string) $offset);
    }

    /**
     * Crea una nueva instancia de ArrayCollection, para usar o asignar a la
     * propiedad $data de la clase.
     *
     * @param array|ArrayAccess|ArrayObject $data
     * @return ArrayCollection
     */
    protected function createFrom(
        array|ArrayAccess|ArrayObject $data
    ): ArrayCollection {
        if ($data instanceof ArrayObject) {
            $data = (array) $data;
        } elseif ($data instanceof ArrayAccess) {
            if ($data instanceof Traversable) {
                $data = iterator_to_array($data);
            } else {
                throw new InvalidArgumentException(
                    'ArrayAccess debe implementar Traversable para ser convertible.'
                );
            }
        }

        return new ArrayCollection($data);
    }

    /**
     * Entrega los elementos de $data como arreglo.
     *
     * @return array
     */
    protected function toArray(): array
    {
        return $this->data->toArray();
    }

    /**
     * {@inheritDoc}
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
