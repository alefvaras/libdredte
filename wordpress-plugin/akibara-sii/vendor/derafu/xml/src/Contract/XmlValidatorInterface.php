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

use Derafu\Xml\Exception\XmlException;

/**
 * Interface for the class that validates an XML document.
 */
interface XmlValidatorInterface
{
    /**
     * Performs the validation of an XML document.
     *
     * @param XmlDocumentInterface $xml The XML document to validate.
     * @param string|null $schemaPath The path to the XSD file of the schema
     * against which to validate. If not specified, it is obtained from the
     * XML document if it is defined in "xsi:schemaLocation".
     * @param array $translations Additional translations to apply.
     * @throws XmlException If the XML is not valid according to its schema.
     */
    public function validate(
        XmlDocumentInterface $xml,
        ?string $schemaPath = null,
        array $translations = []
    ): void;
}
