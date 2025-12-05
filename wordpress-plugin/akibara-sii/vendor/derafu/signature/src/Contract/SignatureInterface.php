<?php

declare(strict_types=1);

/**
 * Derafu: Signature - Library for digital signatures.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Signature\Contract;

use Derafu\Certificate\Contract\CertificateInterface;
use Derafu\Xml\Contract\XmlDocumentInterface;
use LogicException;

/**
 * Interface for the class that represents the electronic signature of an XML.
 */
interface SignatureInterface
{
    /**
     * Assigns the data of the signature node.
     *
     * @param array $data
     * @return static
     */
    public function setData(array $data): static;

    /**
     * Returns the data of the signature node.
     *
     * This is the data structure that allows creating the node as XML.
     *
     * @return array
     */
    public function getData(): array;

    /**
     * Adds the necessary data to the signature node to be able to calculate the
     * signature on these data.
     *
     * @param string $digestValue The calculated DigestValue.
     * @param CertificateInterface $certificate The digital certificate to assign.
     * @param string|null $reference The URI reference, which must include the
     * prefix "#"
     * @return static
     */
    public function configureSignatureData(
        string $digestValue,
        CertificateInterface $certificate,
        ?string $reference = null
    ): static;

    /**
     * Assigns the `Xml` instance built with the data of the signature node.
     *
     * @param XmlDocumentInterface $xml
     * @return static
     */
    public function setXml(XmlDocumentInterface $xml): static;

    /**
     * Returns the `Xml` object that represents the `Signature` node.
     *
     * @return XmlDocumentInterface The `Xml` object with the data of the
     * `Signature` node.
     * @throws LogicException When the `Xml` of the node is not available.
     */
    public function getXml(): XmlDocumentInterface;

    /**
     * Returns the reference associated with the electronic signature, if it
     * exists.
     *
     * @return string|null The reference associated with the `Signature` node,
     * or `null` if it does not have.
     */
    public function getReference(): ?string;

    /**
     * Returns the DigestValue of the `Reference` node.
     *
     * @return string|null The DigestValue, or `null` if it is not defined.
     */
    public function getDigestValue(): ?string;

    /**
     * Returns the X509 certificate associated with the `KeyInfo` node.
     *
     * @return string|null The X509 certificate in base64, or `null` if it is
     * not defined.
     */
    public function getX509Certificate(): ?string;

    /**
     * Sets the calculated signature value for the `SignedInfo` node.
     *
     * @param string $signatureValue The signature value in base64.
     * @return static
     */
    public function setSignatureValue(string $signatureValue): static;

    /**
     * Returns the calculated signature value for the `SignedInfo` node.
     *
     * @return string|null The calculated signature value in base64, or `null`
     * if it is not defined.
     */
    public function getSignatureValue(): ?string;
}
