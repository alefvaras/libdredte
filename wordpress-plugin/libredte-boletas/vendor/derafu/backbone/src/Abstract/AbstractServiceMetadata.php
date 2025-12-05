<?php

declare(strict_types=1);

/**
 * Derafu: Backbone - The Architectural Spine for PHP Libraries.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Backbone\Abstract;

use Derafu\Backbone\Contract\ServiceMetadataInterface;
use LogicException;

/**
 * Abstract class for the metadata of the services.
 */
abstract class AbstractServiceMetadata implements ServiceMetadataInterface
{
    /**
     * {@inheritDoc}
     */
    public function getId(): string
    {
        return $this->id ?? throw new LogicException('The id property is required');
    }

    /**
     * {@inheritDoc}
     */
    public function getName(): string
    {
        return $this->name ?? throw new LogicException('The name property is required');
    }

    /**
     * {@inheritDoc}
     */
    public function getDescription(): ?string
    {
        return $this->description ?? null;
    }

    /**
     * {@inheritDoc}
     */
    public function getGroupId(): string
    {
        if ($this->getParentId() === null) {
            return $this->getType()->value;
        }

        return $this->getParentId() . '#' . $this->getType()->value;
    }

    /**
     * {@inheritDoc}
     */
    public function getCategoryId(): string
    {
        return 'service::' . $this->getType()->value;
    }
}
