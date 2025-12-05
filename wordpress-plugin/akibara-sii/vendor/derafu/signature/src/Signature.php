<?php

declare(strict_types=1);

/**
 * Derafu: Signature - Library for digital signatures.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Signature;

use Derafu\Certificate\Contract\CertificateInterface;
use Derafu\Signature\Contract\SignatureInterface;
use Derafu\Xml\Contract\XmlDocumentInterface;
use LogicException;

/**
 * Class that represents the `Signature` node in an XML digitally signed using
 * the XML digital signature standard (XML DSIG).
 */
final class Signature implements SignatureInterface
{
    /**
     * XML document that represents the electronic signature node.
     *
     * @var XmlDocumentInterface
     */
    private XmlDocumentInterface $xml;

    /**
     * Data of the signature node.
     *
     * By default, the data is left empty and will be completed later either by
     * assigning the data or by loading a new XML with the data.
     *
     * @var array
     */
    private array $data = [
        // Root node is Signature.
        // This is the node that will be included in the signed XML.
        'Signature' => [
            '@attributes' => [
                'xmlns' => 'http://www.w3.org/2000/09/xmldsig#',
            ],
            // Data that will be signed. The most important tag here is the
            // "DigestValue" that contains a "summary" (digest) of the C14N of
            // the reference node.
            'SignedInfo' => [
                '@attributes' => [
                    'xmlns:xsi' => 'http://www.w3.org/2001/XMLSchema-instance',
                ],
                'CanonicalizationMethod' => [
                    '@attributes' => [
                        'Algorithm' => 'http://www.w3.org/TR/2001/REC-xml-c14n-20010315',
                    ],
                ],
                'SignatureMethod' => [
                    '@attributes' => [
                        'Algorithm' => 'http://www.w3.org/2000/09/xmldsig#rsa-sha1',
                    ],
                ],
                'Reference' => [
                    '@attributes' => [
                        // Indicates which is the reference node, it must have a
                        // "#" prefix. If it is empty, it is understood that the
                        // entire XML is to be signed.
                        'URI' => '', // Optional.
                    ],
                    'Transforms' => [
                        'Transform' => [
                            '@attributes' => [
                                'Algorithm' => 'http://www.w3.org/2000/09/xmldsig#enveloped-signature',
                            ],
                        ],
                    ],
                    'DigestMethod' => [
                        '@attributes' => [
                            'Algorithm' => 'http://www.w3.org/2000/09/xmldsig#sha1',
                        ],
                    ],
                    'DigestValue' => '', // Required.
                ],
            ],
            // Signature of the C14N of the `SignedInfo` node.
            // It is added after building the C14N of the SignedInfo and signing.
            'SignatureValue' => '', // Required.
            // Public key information for later validation of the electronic
            // signature.
            'KeyInfo' => [
                'KeyValue' => [
                    'RSAKeyValue' => [
                        'Modulus' => '', // Required.
                        'Exponent' => '', // Required.
                    ],
                ],
                'X509Data' => [
                    'X509Certificate' => '', // Required.
                ],
            ],
        ],
    ];

    /**
     * {@inheritDoc}
     */
    public function setData(array $data): static
    {
        // Assign the data.
        $this->data = $data;

        // Invalidate the XML of the signature node.
        $this->invalidateXml();

        // Return instance for chaining.
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * {@inheritDoc}
     */
    public function configureSignatureData(
        string $digestValue,
        CertificateInterface $certificate,
        ?string $reference = null
    ): static {
        return $this
            ->setReference($reference)
            ->setDigestValue($digestValue)
            ->setCertificate($certificate)
        ;
    }

    /**
     * {@inheritDoc}
     */
    public function setXml(XmlDocumentInterface $xml): static
    {
        $this->xml = $xml;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getXml(): XmlDocumentInterface
    {
        // If the instance has not been assigned previously, an exception is
        // thrown.
        if (!isset($this->xml)) {
            throw new LogicException(
                'The Xml instance is not available in Signature.'
            );
        }

        return $this->xml;
    }

    /**
     * {@inheritDoc}
     */
    public function getReference(): ?string
    {
        $uri = $this->data['Signature']['SignedInfo']['Reference']['@attributes']['URI'];

        return $uri ? ltrim($uri, '#') : null;
    }

    /**
     * {@inheritDoc}
     */
    public function getDigestValue(): ?string
    {
        $digestValue = $this->data['Signature']['SignedInfo']['Reference']['DigestValue'];

        return $digestValue ?: null;
    }

    /**
     * {@inheritDoc}
     */
    public function getX509Certificate(): ?string
    {
        $x509 = $this->data['Signature']['KeyInfo']['X509Data']['X509Certificate'];

        return $x509 ?: null;
    }

    /**
     * {@inheritDoc}
     */
    public function setSignatureValue(string $signatureValue): static
    {
        // Assign the electronic signature of the `SignedInfo` node.
        $this->data['Signature']['SignatureValue'] =
            wordwrap($signatureValue, 64, "\n", true)
        ;

        // Invalidate the XML of the signature node.
        $this->invalidateXml();

        // Return instance for chaining.
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getSignatureValue(): ?string
    {
        $signatureValue = $this->data['Signature']['SignatureValue'];

        return $signatureValue ?: null;
    }

    /**
     * Sets the URI reference for the electronic signature.
     *
     * @param string|null $reference The URI reference, which must include the
     * prefix "#".
     * @return static The current instance for method chaining.
     */
    private function setReference(?string $reference = null): static
    {
        // Assign the URI reference (or empty if the entire XML is signed).
        $uri = $reference ? ('#' . ltrim($reference, '#')) : '';
        $this->data['Signature']['SignedInfo']['Reference']['@attributes']['URI'] = $uri;

        // Assign the transformation algorithm when obtaining the C14N.
        $this->data['Signature']['SignedInfo']['Reference']['Transforms']['Transform']['@attributes']['Algorithm'] = $reference
            ? 'http://www.w3.org/TR/2001/REC-xml-c14n-20010315'
            : 'http://www.w3.org/2000/09/xmldsig#enveloped-signature'
        ;

        // Invalidate the XML of the signature node.
        $this->invalidateXml();

        // Return instance for chaining.
        return $this;
    }

    /**
     * Sets the DigestValue of the `Reference` node.
     *
     * @param string $digestValue The calculated DigestValue.
     * @return static The current instance for method chaining.
     */
    private function setDigestValue(string $digestValue): static
    {
        // Assign the digest value.
        $this->data['Signature']['SignedInfo']['Reference']['DigestValue'] = $digestValue;

        // Invalidate the XML of the signature node.
        $this->invalidateXml();

        // Return instance for chaining.
        return $this;
    }

    /**
     * Assigns a digital certificate to the current instance and updates the
     * corresponding values in the `KeyInfo` node (module, exponent and
     * certificate in X509 format).
     *
     * @param CertificateInterface $certificate The digital certificate to assign.
     * @return static The current instance for method chaining.
     */
    private function setCertificate(CertificateInterface $certificate): static
    {
        // Add module, exponent and certificate. The last one contains the
        // public key that will allow others to validate the XML signature.
        $this->data['Signature']['KeyInfo']['KeyValue']['RSAKeyValue']['Modulus'] =
            $certificate->getModulus()
        ;
        $this->data['Signature']['KeyInfo']['KeyValue']['RSAKeyValue']['Exponent'] =
            $certificate->getExponent()
        ;
        $this->data['Signature']['KeyInfo']['X509Data']['X509Certificate'] =
            $certificate->getCertificate(true)
        ;

        // Invalidate the XML of the signature node.
        $this->invalidateXml();

        // Return instance for chaining.
        return $this;
    }

    /**
     * Invalidates the Xml associated with the signature node.
     *
     * This method is used when assigning data to the node, since the Xml
     * should be regenerated (this is done outside and must be assigned again).
     *
     * The invalidation is done by applying `unset()` to the Xml.
     *
     * @return void
     */
    private function invalidateXml(): void
    {
        unset($this->xml);
    }
}
