<?php

declare(strict_types=1);

/**
 * Derafu: Certificate - Library for digital certificates.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Certificate\Contract;

/**
 * Interface for the class that allows creating a self-signed digital
 * certificate (fake).
 */
interface CertificateFakerInterface
{
    /**
     * Creates a self-signed digital certificate (fake) for testing.
     *
     * @param string|null $id User identifier (RUN).
     * @param string|null $name Name of the user of the certificate.
     * @param string|null $email Email of the user of the certificate.
     * @return CertificateInterface
     */
    public function createFake(
        ?string $id = null,
        ?string $name = null,
        ?string $email = null
    ): CertificateInterface;
}
