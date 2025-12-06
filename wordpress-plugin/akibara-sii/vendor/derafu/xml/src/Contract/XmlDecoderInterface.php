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
 * Interface for the class that decodes an XML to a PHP array.
 */
interface XmlDecoderInterface
{
    /**
     * Converts an XML document to a PHP array.
     *
     * @param XmlDocumentInterface|DOMElement $documentElement XML document that
     * we want to convert to a PHP array or the element where we will make the
     * conversion if it is not the complete XML document.
     * @param array|null $data The array where the results will be stored.
     * @param bool $twinsAsArray Indicates if we should treat twins nodes as an
     * array.
     * @return array The PHP array with the XML representation.
     */
    public function decode(
        XmlDocumentInterface|DOMElement $documentElement,
        ?array &$data = null,
        bool $twinsAsArray = false
    ): array;
}
