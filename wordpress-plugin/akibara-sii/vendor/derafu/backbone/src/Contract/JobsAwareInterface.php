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

use Derafu\Backbone\Exception\JobNotFoundException;

/**
 * Interface for the services that use jobs.
 */
interface JobsAwareInterface
{
    /**
     * Gets a job by its name.
     *
     * @param string $name The name of the job.
     * @return JobInterface The job.
     * @throws JobNotFoundException If the job is not registered.
     */
    public function getJob(string $name): JobInterface;

    /**
     * Gets all jobs.
     *
     * @return array<string, JobInterface> The list of jobs.
     */
    public function getJobs(): array;

    /**
     * Sets the jobs.
     *
     * @param array<string, JobInterface>|iterable $jobs The list of jobs.
     * @return static The instance.
     */
    public function setJobs(iterable $jobs): static;
}
