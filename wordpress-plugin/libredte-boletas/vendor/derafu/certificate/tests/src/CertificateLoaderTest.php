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
use Derafu\Certificate\Certificate;
use Derafu\Certificate\Contract\CertificateFakerInterface;
use Derafu\Certificate\Contract\CertificateInterface;
use Derafu\Certificate\Contract\CertificateLoaderInterface;
use Derafu\Certificate\Exception\CertificateException;
use Derafu\Certificate\SelfSignedCertificate;
use Derafu\Certificate\Service\CertificateFaker;
use Derafu\Certificate\Service\CertificateLoader;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Certificate::class)]
#[CoversClass(CertificateException::class)]
#[CoversClass(CertificateFaker::class)]
#[CoversClass(CertificateFaker::class)]
#[CoversClass(CertificateLoader::class)]
#[CoversClass(AsymmetricKeyHelper::class)]
#[CoversClass(SelfSignedCertificate::class)]
final class CertificateLoaderTest extends TestCase
{
    private CertificateLoaderInterface $loader;

    private CertificateFakerInterface $faker;

    protected function setUp(): void
    {
        $this->loader = new CertificateLoader();
        $this->faker = new CertificateFaker($this->loader);
    }

    public function testCreateFromFile(): void
    {
        $password = 'hola_mundo';

        $data = $this->faker->createFake()->getPkcs12($password);
        $tempFile = tempnam(sys_get_temp_dir(), 'cert');
        file_put_contents($tempFile, $data);

        $certificate = $this->loader->loadFromFile($tempFile, $password);

        $this->assertInstanceOf(CertificateInterface::class, $certificate);
        unlink($tempFile);
    }

    public function testCreateFromData(): void
    {
        $password = 'hola_mundo';

        $data = $this->faker->createFake()->getPkcs12($password);
        $certificate = $this->loader->loadFromData($data, $password);

        $this->assertInstanceOf(CertificateInterface::class, $certificate);
    }

    public function testCreateFromArray(): void
    {
        $certs = $this->faker->createFake()->getKeys();
        $certificate = $this->loader->loadFromArray($certs);
        $this->assertInstanceOf(CertificateInterface::class, $certificate);
    }

    /**
     * Ensures that an exception is thrown when trying to load a certificate
     * file that is not readable.
     */
    public function testCreateFromFileThrowsExceptionForUnreadableFile(): void
    {
        $this->expectException(CertificateException::class);
        $this->expectExceptionMessage('It was not possible to read the digital certificate file from');
        $this->loader->loadFromFile('/path/no/existe/cert.p12', 'testpass');
    }

    /**
     * Ensures that an exception is thrown when trying to load a certificate
     * from corrupted or invalid data.
     */
    public function testCreateFromDataThrowsExceptionForInvalidData(): void
    {
        $this->expectException(CertificateException::class);
        $this->expectExceptionMessage('It was not possible to read the digital certificate data.');
        $invalidData = 'datos_corruptos';
        $this->loader->loadFromData($invalidData, 'testpass');
    }

    /**
     * Ensures that an exception is thrown when the array does not contain a
     * public key.
     */
    public function testCreateFromArrayThrowsExceptionForMissingPublicKey(): void
    {
        $this->expectException(CertificateException::class);
        $this->expectExceptionMessage('The public key of the certificate was not found.');
        $certs = $this->faker->createFake()->getKeys();
        unset($certs['cert']); // Eliminar la clave pública para simular un array inválido.
        $this->loader->loadFromArray($certs);
    }

    /**
     * Ensures that an exception is thrown when the array does not contain a
     * private key.
     */
    public function testCreateFromArrayThrowsExceptionForMissingPrivateKey(): void
    {
        $this->expectException(CertificateException::class);
        $this->expectExceptionMessage('The private key of the certificate was not found.');
        $certs = $this->faker->createFake()->getKeys();
        unset($certs['pkey']); // Delete the private key to simulate an invalid array.
        $this->loader->loadFromArray($certs);
    }
}
