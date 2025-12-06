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

use Derafu\Certificate\Exception\CertificateException;

/**
 * Interface for the certificate validator.
 */
interface CertificateValidatorInterface
{
    /**
     * Performs different validations of the electronic signature.
     *
     * @return void
     * @throws CertificateException
     */
    public function validate(CertificateInterface $certificate): void;
}
