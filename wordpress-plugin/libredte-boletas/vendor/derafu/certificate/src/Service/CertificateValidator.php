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

use Derafu\Certificate\Contract\CertificateInterface;
use Derafu\Certificate\Contract\CertificateValidatorInterface;
use Derafu\Certificate\Exception\CertificateException;

/**
 * Class that performs validations on the digital certificate.
 *
 * NOTE: This validation is to verify that the certificate can be used in an
 * application for electronic invoicing in Chile according to requirements of
 * the SII (Servicio de Impuestos Internos). If you need to validate a
 * certificate for another purpose, you must implement the
 * CertificateValidatorInterface interface with your own logic.
 */
final class CertificateValidator implements CertificateValidatorInterface
{
    /**
     * {@inheritDoc}
     */
    public function validate(CertificateInterface $certificate): void
    {
        // Validate that the ID (RUN) of the certificate is present.
        $id = $certificate->getID(false);

        // Validate that the ID (RUN) contains a DV.
        $dv = explode('-', $id)[1] ?? null;
        if ($dv === null) {
            throw new CertificateException(sprintf(
                'The ID (RUN) %s of the certificate is not valid, it must include "-" (dash).',
                $id
            ));
        }

        // Validate that if the ID (RUN) ends with DV equal to "K", it is uppercase.
        if ($dv === 'k') {
            throw new CertificateException(sprintf(
                'The RUN %s associated with the certificate is not valid, it ends with "k" (lowercase). It is recommended to acquire a new certificate and when purchasing it, verify that the "K" is uppercase. It is not possible to use a RUN that ends with "k" (lowercase) in the digital certificate (electronic signature). The provider of the certificate with the problem is %s.',
                $id,
                $certificate->getIssuer()
            ));
        }

        // Validate that the certificate is active (not expired).
        if (!$certificate->isActive()) {
            throw new CertificateException(sprintf(
                'The certificate expired on %s, it must use a valid certificate. If you do not have one, you must acquire it from an authorized provider by the SII.',
                $certificate->getTo()
            ));
        }
    }
}
