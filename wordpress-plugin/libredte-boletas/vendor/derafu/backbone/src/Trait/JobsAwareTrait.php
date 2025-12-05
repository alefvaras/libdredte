<?php

declare(strict_types=1);

/**
 * Derafu: Backbone - The Architectural Spine for PHP Libraries.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Backbone\Trait;

use Derafu\Backbone\Contract\JobInterface;
use Derafu\Backbone\Exception\JobException;

/**
 * Trait for the services that use jobs.
 */
trait JobsAwareTrait
{
    /**
     * Jobs that the service uses.
     *
     * @var JobInterface[]
     */
    protected array $jobs = [];

    /**
     * Gets a job by its name.
     *
     * @param string $job
     * @return JobInterface
     */
    public function getJob(string $job): JobInterface
    {
        if (!isset($this->jobs[$job])) {
            throw new JobException(sprintf(
                'Job %s not found in service %s (%s).',
                $job,
                $this->getName(),
                $this->getId(),
            ));
        }

        return $this->jobs[$job];
    }

    /**
     * Gets all jobs.
     *
     * @return JobInterface[]
     */
    public function getJobs(): array
    {
        return $this->jobs;
    }

    /**
     * Sets the jobs.
     *
     * @param array|iterable $jobs
     * @return static
     */
    public function setJobs(iterable $jobs): static
    {
        $this->jobs = is_array($jobs)
            ? $jobs
            : iterator_to_array($jobs)
        ;

        return $this;
    }
}
