<?php

declare(strict_types=1);

/**
 * Derafu: Signature - Library for digital signatures.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\TestsSignature;

use Derafu\Certificate\Contract\CertificateInterface;
use Derafu\Certificate\Service\CertificateFaker;
use Derafu\Certificate\Service\CertificateLoader;
use Derafu\Signature\Contract\SignatureServiceInterface;
use Derafu\Signature\Exception\SignatureException;
use Derafu\Signature\Service\SignatureGenerator;
use Derafu\Signature\Service\SignatureService;
use Derafu\Signature\Service\SignatureValidator;
use Derafu\Signature\Signature;
use Derafu\Xml\Exception\XmlException;
use Derafu\Xml\Service\XmlDecoder;
use Derafu\Xml\Service\XmlEncoder;
use Derafu\Xml\Service\XmlService;
use Derafu\Xml\Service\XmlValidator;
use Derafu\Xml\XmlDocument;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Signature::class)]
#[CoversClass(SignatureService::class)]
#[CoversClass(SignatureGenerator::class)]
#[CoversClass(SignatureValidator::class)]
class SignatureServiceTest extends TestCase
{
    private string $xmlDir;

    private SignatureServiceInterface $signatureService;

    private CertificateInterface $certificate;

    protected function setUp(): void
    {
        $this->xmlDir = __DIR__ . '/../fixtures';

        $xmlEncoder = new XmlEncoder();
        $xmlDecoder = new XmlDecoder();
        $xmlValidator = new XmlValidator();
        $xmlService = new XmlService($xmlEncoder, $xmlDecoder, $xmlValidator);

        $signatureGenerator = new SignatureGenerator($xmlService);
        $this->signatureService = new SignatureService(
            $signatureGenerator,
            new SignatureValidator($signatureGenerator, $xmlService)
        );

        $certificateLoader = new CertificateLoader();
        $certificateFaker = new CertificateFaker($certificateLoader);
        $this->certificate = $certificateFaker->createFake();
    }

    public function testSignatureServiceSignXmlString(): void
    {
        $xmlUnsigned = file_get_contents($this->xmlDir . '/unsigned.xml');
        $xmlSigned = $this->signatureService->signXml(
            $xmlUnsigned,
            $this->certificate
        );

        $this->signatureService->validateXml($xmlSigned);
        $this->assertTrue(true);
    }

    public function testSignatureServiceSignXmlObject(): void
    {
        $xmlUnsigned = file_get_contents($this->xmlDir . '/unsigned.xml');
        $xml = new XmlDocument();
        $xml->loadXml($xmlUnsigned);
        $xmlSigned = $this->signatureService->signXml(
            $xml,
            $this->certificate
        );

        $this->signatureService->validateXml($xmlSigned);
        $this->assertTrue(true);
    }

    public function testSignatureServiceSignXmlWithReference(): void
    {
        $xmlUnsigned = file_get_contents($this->xmlDir . '/unsigned.xml');
        $xml = new XmlDocument();
        $xml->loadXml($xmlUnsigned);
        $xmlSigned = $this->signatureService->signXml(
            $xml,
            $this->certificate,
            'Derafu_SetDoc'
        );

        $this->signatureService->validateXml($xmlSigned);
        $this->assertTrue(true);
    }

    public function testSignatureServiceSignXmlWithInvalidReference(): void
    {
        $this->expectException(XmlException::class);

        $xmlUnsigned = file_get_contents($this->xmlDir . '/unsigned.xml');
        $xml = new XmlDocument();
        $xml->loadXml($xmlUnsigned);
        $xmlSigned = $this->signatureService->signXml(
            $xml,
            $this->certificate,
            'Derafu_SetDo'
        );
    }

    public function testSignatureServiceValidXmlSignature(): void
    {
        $xml = file_get_contents($this->xmlDir . '/valid_signed.xml');
        $this->signatureService->validateXml($xml);
        $this->assertTrue(true);
    }

    public function testSignatureServiceInvalidXmlSignature(): void
    {
        $this->expectException(SignatureException::class);

        $xml = file_get_contents($this->xmlDir . '/invalid_signed.xml');
        $this->signatureService->validateXml($xml);
    }
}
