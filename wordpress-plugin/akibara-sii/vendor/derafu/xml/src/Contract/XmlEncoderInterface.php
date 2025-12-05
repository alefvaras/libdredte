<?php

declare(strict_types=1);

/**
 * Derafu: XML - Library for XML manipulation.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Xml\Contract;

use DOMElement;

/**
 * Interface for the class that encodes a PHP array to XML.
 */
interface XmlEncoderInterface
{
    /**
     * Converts a PHP array to an XML document, generating the nodes and
     * respecting a namespace if provided.
     *
     * @param array $data The array with the data that will be used to generate
     * XML.
     * @param array|null $namespace The namespace for the XML (URI and prefix).
     * @param DOMElement|null $parent The parent element for the nodes, or null
     * to be the root.
     * @param XmlDocumentInterface $doc The root XML document that will be
     * generated.
     * @return XmlDocumentInterface
     */
    public function encode(
        array $data,
        ?array $namespace = null,
        ?DOMElement $parent = null,
        ?XmlDocumentInterface $doc = null
    ): XmlDocumentInterface;
}
