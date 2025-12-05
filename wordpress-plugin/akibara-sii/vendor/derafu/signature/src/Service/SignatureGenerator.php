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
use Derafu\Signature\Exception\SignatureException;
use Derafu\Signature\Signature;
use Derafu\Xml\Contract\XmlDocumentInterface;
use Derafu\Xml\Contract\XmlServiceInterface;
use Derafu\Xml\XmlDocument;
use LogicException;

/**
 * Class that handles the generation of electronic signatures, particularly for
 * XML documents.
 */
final class SignatureGenerator implements SignatureGeneratorInterface
{
    /**
     * Constructor of the signature generator.
     *
     * @param XmlServiceInterface $xmlService
     */
    public function __construct(private readonly XmlServiceInterface $xmlService)
    {
    }

    /**
     * {@inheritDoc}
     */
    public function sign(
        string $data,
        string $privateKey,
        string|int $signatureAlgorithm = OPENSSL_ALGO_SHA1
    ): string {
        // Sign the data.
        $signature = null;
        $result = openssl_sign(
            $data,
            $signature,
            $privateKey,
            $signatureAlgorithm
        );

        // If the data could not be signed, an exception is thrown.
        if ($result === false) {
            throw new SignatureException('Could not sign the data.');
        }

        // Return the signature in base64.
        return base64_encode($signature);
    }

    /**
     * {@inheritDoc}
     */
    public function signXml(
        XmlDocumentInterface|string $xml,
        CertificateInterface $certificate,
        ?string $reference = null
    ): string {
        // If an Xml object is passed, it is converted to a string. This is
        // necessary to keep the "pretty" format if it was passed and to be able
        // to get the C14N correctly.
        if (!is_string($xml)) {
            $xml = $xml->saveXml();
        }

        // Load the XML that will be signed.
        $doc = new XmlDocument();
        $doc->loadXml($xml);
        if (!$doc->documentElement) {
            throw new SignatureException(
                'Could not get the documentElement from the XML to sign (possible malformed XML).'
            );
        }

        // Generate the "DigestValue" of the reference data.
        $digestValue = $this->generateXmlDigestValue($doc, $reference);

        // Create the instance that represents the signature node with its data.
        $signatureNode = (new Signature())->configureSignatureData(
            reference: $reference,
            digestValue: $digestValue,
            certificate: $certificate
        );

        // Sign the document by calculating the value of the signature of the
        // `Signature` node.
        $signatureNode = $this->signSignature(
            $signatureNode,
            $certificate
        );
        $xmlSignature = $signatureNode->getXml()->getXml();

        // Add the XML signature in the Signature node.
        $signatureElement = $doc->createElement('Signature', '');
        $doc->documentElement->appendChild($signatureElement);
        $xmlSigned = str_replace('<Signature/>', $xmlSignature, $doc->saveXml());

        // Return the string XML of the signed XML document.
        return $xmlSigned;
    }

    /**
     * {@inheritDoc}
     */
    public function generateXmlDigestValue(
        XmlDocumentInterface $doc,
        ?string $reference = null
    ): string {
        // The digest will be made of a specific reference (ID) in the XML.
        if (!empty($reference)) {
            $xpath = '//*[@ID="' . ltrim($reference, '#') . '"]';
            $dataToDigest = $doc->C14NWithIso88591Encoding($xpath);
        }
        // When there is no reference, the digest is over the entire XML.
        // If the XML already has a "Signature" node within the root node, it
        // must be removed before obtaining its C14N.
        else {
            $docClone = clone $doc;
            $rootElement = $docClone->getDocumentElement();
            $signatureElement = $rootElement
                ->getElementsByTagName('Signature')
                ->item(0)
            ;
            if ($signatureElement) {
                $rootElement->removeChild($signatureElement);
            }
            $dataToDigest = $docClone->C14NWithIso88591Encoding();
        }

        // Calculate the digest over the XML data in C14N format.
        $digestValue = base64_encode(sha1($dataToDigest, true));

        // Return the calculated digest.
        return $digestValue;
    }

    /**
     * Sign the `SignedInfo` node of the XML document using a digital
     * certificate. If a certificate was not previously provided, it can be
     * passed as an argument in the signature.
     *
     * @return SignatureInterface The signature node that will be signed.
     * @param CertificateInterface $certificate Digital certificate to use for signing.
     * @return SignatureInterface The signed signature node.
     * @throws LogicException If the conditions to sign are not met.
     */
    private function signSignature(
        SignatureInterface $signatureNode,
        CertificateInterface $certificate
    ): SignatureInterface {
        // Validate that the DigestValue is assigned.
        if ($signatureNode->getDigestValue() === null) {
            throw new LogicException(
                'It is not possible to generate the signature of the Signature node if the DigestValue is not assigned.'
            );
        }

        // Validate that the digital certificate is assigned.
        if ($signatureNode->getX509Certificate() === null) {
            throw new LogicException(
                'It is not possible to generate the signature of the Signature node if the digital certificate is not assigned.'
            );
        }

        // Create the XML document of the electronic signature node.
        $nodeXml = $this->createSignatureNodeXml(
            $signatureNode
        );

        // Generate the string XML of the data that will be signed.
        $xpath = "//*[local-name()='Signature']/*[local-name()='SignedInfo']";
        $signedInfoC14N = $nodeXml->C14NWithIso88591Encoding($xpath);

        // Generate the signature of the data, the tag `SignedInfo`.
        $signature = $this->sign(
            $signedInfoC14N,
            $certificate->getPrivateKey()
        );

        // Assign the calculated signature to the signature node.
        $signatureNode->setSignatureValue($signature);

        // Recreate the XML of the signature node.
        $this->createSignatureNodeXml($signatureNode);

        // Return the signature node.
        return $signatureNode;
    }

    /**
     * Creates the Xml instance of Signature and assigns it to this.
     *
     * @param SignatureInterface $signatureNode
     * @return XmlDocumentInterface
     */
    private function createSignatureNodeXml(
        SignatureInterface $signatureNode
    ): XmlDocumentInterface {
        $xml = new XmlDocument();
        $xml->formatOutput = false;
        $xml = $this->xmlService->encode(
            data: $signatureNode->getData(),
            doc: $xml // Pass the Xml to assign formatOutput.
        );

        $signatureNode->setXml($xml);

        return $signatureNode->getXml();
    }
}
