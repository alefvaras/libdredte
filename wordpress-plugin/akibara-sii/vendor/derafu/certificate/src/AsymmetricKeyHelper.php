<?php

declare(strict_types=1);

/**
 * Derafu: Certificate - Library for digital certificates.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Certificate;

use Derafu\Certificate\Exception\CertificateException;
use LogicException;
use phpseclib3\Crypt\PublicKeyLoader;
use phpseclib3\Math\BigInteger;

/**
 * Class that provides common utilities for RSA certificates that use asymmetric
 * encryption. These are used, for example, in electronic signature.
 */
class AsymmetricKeyHelper
{
    /**
     * Normalizes a public key (certificate) by adding headers and footers if
     * necessary.
     *
     * @param string $publicKey Public key that you want to normalize.
     * @param int $wordwrap Length to which each line of the file must be left.
     * @return string Normalized public key.
     */
    public static function normalizePublicKey(
        string $publicKey,
        int $wordwrap = 64
    ): string {
        if (empty($publicKey)) {
            throw new CertificateException(
                'The public key cannot be empty.'
            );
        }

        if (!str_contains($publicKey, '-----BEGIN CERTIFICATE-----')) {
            $body = trim($publicKey);
            $publicKey = '-----BEGIN CERTIFICATE-----' . "\n";
            $publicKey .= wordwrap($body, $wordwrap, "\n", true) . "\n";
            $publicKey .= '-----END CERTIFICATE-----' . "\n";
        }

        return $publicKey;
    }

    /**
     * Normalizes a private key by adding headers and footers if necessary.
     *
     * @param string $privateKey Private key that you want to normalize.
     * @param int $wordwrap Length to which each line of the file must be
     * left.
     * @return string Normalized private key.
     */
    public static function normalizePrivateKey(
        string $privateKey,
        int $wordwrap = 64
    ): string {
        if (empty($privateKey)) {
            throw new CertificateException(
                'The private key cannot be empty.'
            );
        }

        if (!str_contains($privateKey, '-----BEGIN PRIVATE KEY-----')) {
            $body = trim($privateKey);
            $privateKey = '-----BEGIN PRIVATE KEY-----' . "\n";
            $privateKey .= wordwrap($body, $wordwrap, "\n", true) . "\n";
            $privateKey .= '-----END PRIVATE KEY-----' . "\n";
        }

        return $privateKey;
    }

    /**
     * Generates a public key from a modulus and an exponent.
     *
     * @param string $modulus Modulus of the key.
     * @param string $exponent Exponent of the key.
     * @return string Generated public key.
     */
    public static function generatePublicKeyFromModulusExponent(
        string $modulus,
        string $exponent
    ): string {
        if (!class_exists(BigInteger::class)) {
            throw new LogicException(
                'First install phpseclib3/phpseclib: composer require phpseclib/phpseclib'
            );
        }

        $modulus = new BigInteger(base64_decode($modulus), 256);
        $exponent = new BigInteger(base64_decode($exponent), 256);

        $rsa = PublicKeyLoader::load([
            'n' => $modulus,
            'e' => $exponent,
        ]);

        return (string) $rsa->toString('PKCS1');
    }
}
