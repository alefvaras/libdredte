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
 * Interface for the certificate service.
 */
interface CertificateServiceInterface extends CertificateLoaderInterface, CertificateFakerInterface, CertificateValidatorInterface
{
}
