<?php

declare(strict_types=1);

/**
 * Derafu: Container - Flexible Data Containers for PHP.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Container;

use ArrayAccess;
use ArrayObject;
use Derafu\Container\Abstract\AbstractContainer;
use Derafu\Container\Contract\JournalInterface;

/**
 * Clase para almacenamiento secuencial de elementos.
 */
class Journal extends AbstractContainer implements JournalInterface
{
    /**
     * Constructor del Journal.
     *
     * @param array|ArrayAccess|ArrayObject $data Datos iniciales del journal.
     */
    public function __construct(array|ArrayAccess|ArrayObject $data = [])
    {
        $this->data = $this->createFrom($data);
    }

    /**
     * {@inheritDoc}
     */
    public function add(mixed $item): static
    {
        $this->data[] = $item;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function reverse(): array
    {
        return array_reverse($this->toArray());
    }
}
