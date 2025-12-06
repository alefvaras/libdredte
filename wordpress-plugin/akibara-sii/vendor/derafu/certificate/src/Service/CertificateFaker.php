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
use Derafu\Certificate\SelfSignedCertificate;

/**
 * Class that generates self-signed certificates (for testing).
 */
final class CertificateFaker implements CertificateFakerInterface
{
    /**
     * Constructor.
     *
     * @param CertificateLoaderInterface $loader
     */
    public function __construct(
        private readonly CertificateLoaderInterface $loader
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function createFake(
        ?string $id = null,
        ?string $name = null,
        ?string $email = null,
        ?string $password = null
    ): CertificateInterface {
        $faker = new SelfSignedCertificate();

        $faker->setSubject(
            serialNumber: $id ?? '11222333-9',
            CN: $name ?? 'Daniel Bot',
            emailAddress: $email ?? 'daniel.bot@example.com'
        );

        if ($password !== null) {
            $faker->setPassword($password);
        }

        $array = $faker->toArray();

        return $this->loader->loadFromArray($array);
    }
}
