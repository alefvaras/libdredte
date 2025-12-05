<?php

declare(strict_types=1);

/**
 * Derafu: Signature - Library for digital signatures.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Signature\Service;

use Derafu\Certificate\Contract\CertificateInterface;
use Derafu\Signature\Contract\SignatureGeneratorInterface;
use Derafu\Signature\Contract\SignatureInterface;
use Derafu\Signature\Contract\SignatureServiceInterface;
use Derafu\Signature\Contract\SignatureValidatorInterface;
use Derafu\Xml\Contract\XmlDocumentInterface;

/**
 * Electronic signature service.
 */
final class SignatureService implements SignatureServiceInterface
{
    /**
     * Constructor of the electronic signature service.
     *
     * @param SignatureGeneratorInterface $generator
     * @param SignatureValidatorInterface $validator
     */
    public function __construct(
        private readonly SignatureGeneratorInterface $generator,
        private readonly SignatureValidatorInterface $validator
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function sign(
        string $data,
        string $privateKey,
        string|int $signatureAlgorithm = OPENSSL_ALGO_SHA1
    ): string {
        return $this->generator->sign($data, $privateKey, $signatureAlgorithm);
    }

    /**
     * {@inheritDoc}
     */
    public function signXml(
        XmlDocumentInterface|string $xml,
        CertificateInterface $certificate,
        ?string $reference = null
    ): string {
        return $this->generator->signXml($xml, $certificate, $reference);
    }

    /**
     * {@inheritDoc}
     */
    public function generateXmlDigestValue(
        XmlDocumentInterface $doc,
        ?string $reference = null
    ): string {
        return $this->generator->generateXmlDigestValue($doc, $reference);
    }

    /**
     * {@inheritDoc}
     */
    public function validate(
        string $data,
        string $signature,
        string $publicKey,
        string|int $signatureAlgorithm = OPENSSL_ALGO_SHA1
    ): bool {
        return $this->validator->validate(
            $data,
            $signature,
            $publicKey,
            $signatureAlgorithm
        );
    }

    /**
     * {@inheritDoc}
     */
    public function validateXml(XmlDocumentInterface|string $xml): void
    {
        $this->validator->validateXml($xml);
    }

    /**
     * {@inheritDoc}
     */
    public function createSignatureNode(string $xml): SignatureInterface
    {
        return $this->validator->createSignatureNode($xml);
    }

    /**
     * {@inheritDoc}
     */
    public function validateXmlDigestValue(
        XmlDocumentInterface|string $xml,
        SignatureInterface $signatureNode
    ): void {
        $this->validator->validateXmlDigestValue($xml, $signatureNode);
    }

    /**
     * {@inheritDoc}
     */
    public function validateXmlSignatureValue(
        SignatureInterface $signatureNode
    ): void {
        $this->validator->validateXmlSignatureValue($signatureNode);
    }
}
