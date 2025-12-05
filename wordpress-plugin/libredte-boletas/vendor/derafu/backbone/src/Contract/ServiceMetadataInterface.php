<?php

declare(strict_types=1);

/**
 * Derafu: Backbone - The Architectural Spine for PHP Libraries.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Backbone\Contract;

use Derafu\Backbone\Enum\ServiceType;

/**
 * Interface for the metadata of the services.
 */
interface ServiceMetadataInterface
{
    /**
     * Gets the type of the service.
     *
     * @return ServiceType
     */
    public function getType(): ServiceType;

    /**
     * Gets the id of the service.
     *
     * @return string
     */
    public function getId(): string;

    /**
     * Gets the name of the service.
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Gets the description of the service.
     *
     * @return string|null
     */
    public function getDescription(): ?string;

    /**
     * Gets the parent id of the service.
     *
     * @return string|null
     */
    public function getParentId(): ?string;

    /**
     * Gets the group of the service.
     *
     * @return string
     */
    public function getGroupId(): string;

    /**
     * Gets the category id of the service.
     *
     * @return string
     */
    public function getCategoryId(): string;

    /**
     * Gets the metadata of the service as an array.
     *
     * @return array<string,string|null>
     */
    public function toArray(): array;
}
