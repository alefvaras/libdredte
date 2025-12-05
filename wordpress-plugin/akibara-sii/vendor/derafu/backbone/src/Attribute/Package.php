<?php

declare(strict_types=1);

/**
 * Derafu: Backbone - The Architectural Spine for PHP Libraries.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Backbone\Attribute;

use Attribute;
use Derafu\Backbone\Abstract\AbstractServiceMetadata;
use Derafu\Backbone\Contract\ServiceMetadataInterface;
use Derafu\Backbone\Enum\ServiceType;

/**
 * Attribute for the packages of the application.
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class Package extends AbstractServiceMetadata implements ServiceMetadataInterface
{
    /**
     * The ID of the package.
     *
     * @var string
     */
    public readonly string $id;

    /**
     * Constructor.
     *
     * @param string $name The name of the package.
     * @param string|null $description The description of the package.
     */
    public function __construct(
        public readonly string $name,
        public readonly ?string $description = null
    ) {
        $this->id = $this->name;
    }

    /**
     * {@inheritDoc}
     */
    public function getType(): ServiceType
    {
        return ServiceType::PACKAGE;
    }

    /**
     * {@inheritDoc}
     */
    public function getParentId(): ?string
    {
        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function toArray(): array
    {
        return [
            'type' => $this->getType()->value,
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
        ];
    }
}
