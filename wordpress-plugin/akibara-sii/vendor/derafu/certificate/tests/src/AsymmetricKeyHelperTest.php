<?php

declare(strict_types=1);

/**
 * Derafu: Certificate - Library for digital certificates.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\TestsCertificate;

use Derafu\Certificate\AsymmetricKeyHelper;
use phpseclib3\Crypt\PublicKeyLoader;
use phpseclib3\Math\BigInteger;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(AsymmetricKeyHelper::class)]
final class AsymmetricKeyHelperTest extends TestCase
{
    /**
     * Verifies that `normalizePublicKey()` adds the headers and footers
     * correctly when the certificate does not have them.
     */
    public function testAsymmetricKeyNormalizePublicKeyWithoutHeaders(): void
    {
        $certBody = "MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEA7sN2a9z8/PQleNzl+Tbh...";
        $expectedCert = "-----BEGIN CERTIFICATE-----\nMIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEA7sN2a9z8/PQleNzl+Tbh\n...\n-----END CERTIFICATE-----\n";

        $normalizedCert = AsymmetricKeyHelper::normalizePublicKey($certBody);

        $this->assertSame($expectedCert, $normalizedCert);
    }

    /**
     * Verifies that `normalizePublicKey()` does not modify a certificate that
     * already has headers and footers.
     */
    public function testAsymmetricKeyNormalizePublicKeyWithHeaders(): void
    {
        $cert = <<<CERT
        -----BEGIN CERTIFICATE-----
        MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEA7sN2a9z8/PQleNzl+Tbh...
        -----END CERTIFICATE-----
        CERT;

        $normalizedCert = AsymmetricKeyHelper::normalizePublicKey($cert);

        $this->assertSame($cert, $normalizedCert);
    }

    /**
     * Verifies that `normalizePublicKey()` respects the `wordwrap` when adding
     * headers and footers.
     */
    public function testAsymmetricKeyNormalizePublicKeyWithCustomWordwrap(): void
    {
        $certBody = "MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEA7sN2a9z8/PQleNzl+Tbh...";
        $wordwrap = 10;
        $expectedCert = "-----BEGIN CERTIFICATE-----\nMIIBIjANBg\nkqhkiG9w0B\nAQEFAAOCAQ\n8AMIIBCgKC\nAQEA7sN2a9\nz8/PQleNzl\n+Tbh...\n-----END CERTIFICATE-----\n";

        $normalizedCert = AsymmetricKeyHelper::normalizePublicKey($certBody, $wordwrap);

        $this->assertSame($expectedCert, $normalizedCert);
    }

    /**
     * Verifies that `normalizePrivateKey()` adds the headers and footers
     * correctly when the certificate does not have them.
     */
    public function testAsymmetricKeyNormalizePrivateKeyWithoutHeaders(): void
    {
        $certBody = "MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEA7sN2a9z8/PQleNzl+Tbh...";
        $expectedCert = "-----BEGIN PRIVATE KEY-----\nMIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEA7sN2a9z8/PQleNzl+Tbh\n...\n-----END PRIVATE KEY-----\n";

        $normalizedCert = AsymmetricKeyHelper::normalizePrivateKey($certBody);

        $this->assertSame($expectedCert, $normalizedCert);
    }

    /**
     * Verifies that `normalizePrivateKey()` does not modify a certificate that
     * already has headers and footers.
     */
    public function testAsymmetricKeyNormalizePrivateKeyWithHeaders(): void
    {
        $cert = <<<CERT
        -----BEGIN PRIVATE KEY-----
        MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEA7sN2a9z8/PQleNzl+Tbh...
        -----END PRIVATE KEY-----
        CERT;

        $normalizedCert = AsymmetricKeyHelper::normalizePrivateKey($cert);

        $this->assertSame($cert, $normalizedCert);
    }

    /**
     * Verifies that `normalizePrivateKey()` respects the `wordwrap` when adding
     * headers and footers.
     */
    public function testAsymmetricKeyNormalizePrivateKeyWithCustomWordwrap(): void
    {
        $certBody = "MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEA7sN2a9z8/PQleNzl+Tbh...";
        $wordwrap = 10;
        $expectedCert = "-----BEGIN PRIVATE KEY-----\nMIIBIjANBg\nkqhkiG9w0B\nAQEFAAOCAQ\n8AMIIBCgKC\nAQEA7sN2a9\nz8/PQleNzl\n+Tbh...\n-----END PRIVATE KEY-----\n";

        $normalizedCert = AsymmetricKeyHelper::normalizePrivateKey($certBody, $wordwrap);

        $this->assertSame($expectedCert, $normalizedCert);
    }

    /**
     * Verifies that `generatePublicKeyFromModulusExponent()` generates a public
     * key correctly from modulus and exponent.
     */
    public function testAsymmetricKeyGeneratePublicKeyFromModulusExponent(): void
    {
        // These values are only examples; in practice, you would use real values.
        $modulus = base64_encode((new BigInteger('1234567890'))->toBytes());
        $exponent = base64_encode((new BigInteger('65537'))->toBytes());

        // Generate the expected public key manually.
        $rsa = PublicKeyLoader::load([
            'n' => new BigInteger(base64_decode($modulus), 256),
            'e' => new BigInteger(base64_decode($exponent), 256),
        ]);
        $expectedPublicKey = $rsa->toString('PKCS1');

        $publicKey = AsymmetricKeyHelper::generatePublicKeyFromModulusExponent($modulus, $exponent);

        $this->assertSame($expectedPublicKey, $publicKey);
    }
}
