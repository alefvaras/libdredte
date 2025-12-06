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

use Derafu\Certificate\AsymmetricKeyHelper;
use Derafu\Signature\Contract\SignatureGeneratorInterface;
use Derafu\Signature\Contract\SignatureInterface;
use Derafu\Signature\Contract\SignatureValidatorInterface;
use Derafu\Signature\Exception\SignatureException;
use Derafu\Signature\Signature;
use Derafu\Xml\Contract\XmlDocumentInterface;
use Derafu\Xml\Contract\XmlServiceInterface;
use Derafu\Xml\XmlDocument;

/**
 * Class that handles the validation of electronic signatures.
 */
final class SignatureValidator implements SignatureValidatorInterface
{
    public function __construct(
        private readonly SignatureGeneratorInterface $generator,
        private readonly XmlServiceInterface $xmlService
    ) {
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
        $publicKey = AsymmetricKeyHelper::normalizePublicKey($publicKey);

        $result = openssl_verify(
            $data,
            base64_decode($signature),
            $publicKey,
            $signatureAlgorithm
        );

        if ($result === -1) {
            throw new SignatureException(
                'An error occurred while verifying the electronic signature of the data.'
            );
        }

        return $result === 1;
    }

    /**
     * {@inheritDoc}
     */
    public function validateXml(XmlDocumentInterface|string $xml): void
    {
        // If an Xml object is passed, it is converted to a string.
        if (!is_string($xml)) {
            $xml = $xml->saveXml();
        }

        // Load the string XML into an XML document.
        $doc = new XmlDocument();
        $doc->loadXml($xml);
        if (!$doc->documentElement) {
            throw new SignatureException(
                'Could not get the documentElement from the XML to validate its signature (possible malformed XML).'
            );
        }

        // Search for all elements that are tag Signature.
        // An XML document can have more than one signature.
        $signaturesElements = $doc->documentElement->getElementsByTagName(
            'Signature'
        );

        // If no signatures are found in the XML, an error is thrown.
        if (!$signaturesElements->length) {
            throw new SignatureException(
                'No signatures were found in the XML to validate.'
            );
        }

        // Iterate each signature found.
        foreach ($signaturesElements as $signatureElement) {
            // Build the signature node instance.
            $signatureNode = $this->createSignatureNode(
                $signatureElement->C14N()
            );

            // Validate the electronic signature node.
            $this->validateXmlDigestValue($doc, $signatureNode);
            $this->validateXmlSignatureValue($signatureNode);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function createSignatureNode(string $xml): SignatureInterface
    {
        $signatureNode = new Signature();

        $signature = new XmlDocument();
        $signature->formatOutput = false;
        $signature->loadXml($xml);

        $data = $this->xmlService->decode($signature);

        // Important: The order is crucial, since setData() invalidates the Xml
        // if it was previously assigned.
        $signatureNode->setData($data);
        $signatureNode->setXml($signature);

        return $signatureNode;
    }

    /**
     * {@inheritDoc}
     */
    public function validateXmlDigestValue(
        XmlDocumentInterface|string $xml,
        SignatureInterface $signatureNode
    ): void {
        // If an Xml object is passed, it is converted to a string.
        if (!is_string($xml)) {
            $xml = $xml->saveXml();
        }

        // Load the string XML into an XML document.
        $doc = new XmlDocument();
        $doc->loadXml($xml);
        if (!$doc->documentElement) {
            throw new SignatureException(
                'Could not get the documentElement from the XML to validate its signature (possible malformed XML).'
            );
        }

        // Get the digest value that comes in the XML (in the signature node).
        $digestValueXml = $signatureNode->getDigestValue();

        // Calculate the digest value from the XML document.
        $digestValueCalculated = $this->generator->generateXmlDigestValue(
            $doc,
            $signatureNode->getReference()
        );

        // If the digest values do not match, it is not valid.
        if ($digestValueXml !== $digestValueCalculated) {
            throw new SignatureException(sprintf(
                'The DigestValue that comes in the XML "%s" for the reference "%s" does not match the calculated value when validating "%s". The data of the reference could have been manipulated after being signed.',
                $digestValueXml,
                $signatureNode->getReference(),
                $digestValueCalculated
            ));
        }
    }

    /**
     * {@inheritDoc}
     */
    public function validateXmlSignatureValue(
        SignatureInterface $signatureNode
    ): void {
        // Generate the string XML of the data that will be validated.
        $xpath = "//*[local-name()='Signature']/*[local-name()='SignedInfo']";
        $signedInfoC14N = $signatureNode
            ->getXml()
            ->C14NWithIso88591Encoding($xpath)
        ;

        // Validate the electronic signature.
        $isValid = $this->validate(
            $signedInfoC14N,
            $signatureNode->getSignatureValue(),
            $signatureNode->getX509Certificate()
        );

        // If the electronic signature is not valid, an exception is thrown.
        if (!$isValid) {
            throw new SignatureException(sprintf(
                'The electronic signature of the `SignedInfo` node of the XML for the reference "%s" is not valid.',
                $signatureNode->getReference()
            ));
        }
    }
}
