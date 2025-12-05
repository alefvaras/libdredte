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
 * Interface for the certificate loader.
 */
interface CertificateLoaderInterface
{
    /**
     * Creates a Certificate instance from a file that contains the digital
     * certificate in PKCS#12 format.
     *
     * @param string $filepath Path to the file that contains the certificate in
     * PKCS#12 format.
     * @param string $password Password to access the certificate content.
     * @return CertificateInterface Instance of the Certificate class that
     * contains the private key and the public certificate.
     * @throws CertificateException If the file cannot be read or the
     * certificate cannot be loaded.
     */
    public function loadFromFile(
        string $filepath,
        string $password
    ): CertificateInterface;

    /**
     * Creates a Certificate instance from a string that contains the digital
     * certificate in PKCS#12 format.
     *
     * @param string $data String that contains the certificate data in PKCS#12
     * format.
     * @param string $password Password to access the certificate content.
     * @return CertificateInterface Instance of the Certificate class that
     * contains the private key and the public certificate.
     * @throws CertificateException If the certificate cannot be loaded
     */
    public function loadFromData(
        string $data,
        string $password
    ): CertificateInterface;

    /**
     * Creates a Certificate instance from an array that contains the public and
     * private keys.
     *
     * @param array $data Array that contains the keys 'publicKey' (or 'cert')
     * and 'privateKey' (or 'pkey').
     * @return CertificateInterface Instance of the Certificate class that
     * contains the private key and the public certificate.
     */
    public function loadFromArray(array $data): CertificateInterface;

    /**
     * Creates a Certificate instance from a public key and a private key.
     *
     * @param string $publicKey Public key of the certificate.
     * @param string $privateKey Private key associated with the certificate.
     * @return CertificateInterface Instance of the Certificate class that
     * contains the private key and the public certificate.
     */
    public function loadFromKeys(
        string $publicKey,
        string $privateKey
    ): CertificateInterface;
}
