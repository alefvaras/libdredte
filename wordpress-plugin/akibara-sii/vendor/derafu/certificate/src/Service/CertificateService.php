<?php

declare(strict_types=1);

/**
 * Derafu: Certificate - Library for digital certificates.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Certificate\Service;

use Derafu\Certificate\Contract\CertificateFakerInterface;
use Derafu\Certificate\Contract\CertificateInterface;
use Derafu\Certificate\Contract\CertificateLoaderInterface;
use Derafu\Certificate\Contract\CertificateServiceInterface;
use Derafu\Certificate\Contract\CertificateValidatorInterface;

/**
 * Service that manages everything related to digital certificates (aka:
 * electronic signatures).
 */
final class CertificateService implements CertificateServiceInterface
{
    /**
     * Constructor.
     *
     * @param CertificateFakerInterface $faker
     * @param CertificateLoaderInterface $loader
     * @param CertificateValidatorInterface $validator
     */
    public function __construct(
        private readonly CertificateFakerInterface $faker,
        private readonly CertificateLoaderInterface $loader,
        private readonly CertificateValidatorInterface $validator,
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function createFake(
        ?string $id = null,
        ?string $name = null,
        ?string $email = null
    ): CertificateInterface {
        return $this->faker->createFake($id, $name, $email);
    }

    /**
     * {@inheritDoc}
     */
    public function loadFromFile(
        string $filepath,
        string $password
    ): CertificateInterface {
        return $this->loader->loadFromFile($filepath, $password);
    }

    /**
     * {@inheritDoc}
     */
    public function loadFromData(
        string $data,
        string $password
    ): CertificateInterface {
        return $this->loader->loadFromData($data, $password);
    }

    /**
     * {@inheritDoc}
     */
    public function loadFromArray(array $data): CertificateInterface
    {
        return $this->loader->loadFromArray($data);
    }

    /**
     * {@inheritDoc}
     */
    public function loadFromKeys(
        string $publicKey,
        string $privateKey
    ): CertificateInterface {
        return $this->loader->loadFromKeys($publicKey, $privateKey);
    }

    /**
     * {@inheritDoc}
     */
    public function validate(CertificateInterface $certificate): void
    {
        $this->validator->validate($certificate);
    }
}
