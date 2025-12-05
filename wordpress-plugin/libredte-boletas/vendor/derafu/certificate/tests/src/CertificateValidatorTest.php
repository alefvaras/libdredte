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
use Derafu\Certificate\Contract\CertificateValidatorInterface;
use Derafu\Certificate\Exception\CertificateException;
use Derafu\Certificate\SelfSignedCertificate;
use Derafu\Certificate\Service\CertificateFaker;
use Derafu\Certificate\Service\CertificateLoader;
use Derafu\Certificate\Service\CertificateValidator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(CertificateValidator::class)]
#[CoversClass(Certificate::class)]
#[CoversClass(CertificateException::class)]
#[CoversClass(SelfSignedCertificate::class)]
#[CoversClass(CertificateFaker::class)]
#[CoversClass(CertificateLoader::class)]
#[CoversClass(AsymmetricKeyHelper::class)]
final class CertificateValidatorTest extends TestCase
{
    private CertificateFakerInterface $faker;

    private CertificateValidatorInterface $validator;

    protected function setUp(): void
    {
        $loader = new CertificateLoader();
        $this->faker = new CertificateFaker($loader);
        $this->validator = new CertificateValidator();
    }

    public function testValidCertificate(): void
    {
        $certificate = $this->faker->createFake();
        $this->validator->validate($certificate);
        $this->assertTrue(true);
    }

    public function testInvalidCertificate(): void
    {
        $this->expectException(CertificateException::class);
        $certificate = $this->faker->createFake(id: '123');
        $this->validator->validate($certificate);
    }
}
