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
 * Attribute for the jobs of the application.
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class Job extends AbstractServiceMetadata implements ServiceMetadataInterface
{
    /**
     * The ID of the job.
     *
     * @var string
     */
    public readonly string $id;

    /**
     * Constructor.
     *
     * @param string $name The name of the job.
     * @param string $worker The name of the worker of the job.
     * @param string $component The name of the component of the job.
     * @param string $package The name of the package of the job.
     * @param string|null $description The description of the job.
     */
    public function __construct(
        public readonly string $name,
        public readonly string $worker,
        public readonly string $component,
        public readonly string $package,
        public readonly ?string $description = null
    ) {
        $this->id = $this->getParentId() . '.job:' . $this->name;
    }

    /**
     * {@inheritDoc}
     */
    public function getType(): ServiceType
    {
        return ServiceType::JOB;
    }

    /**
     * {@inheritDoc}
     */
    public function getParentId(): string
    {
        return $this->package . '.' . $this->component . '.' . $this->worker;
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
            'worker' => $this->worker,
            'component' => $this->component,
            'package' => $this->package,
            'description' => $this->description,
        ];
    }
}
