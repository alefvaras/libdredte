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
use DOMNodeList;
use DOMParentNode;

/**
 * Interface for the class that represents an XML document.
 *
 * This interface exists so that other interfaces that extend DOMDocument, such
 * as XmlDocument, can resolve the methods that use the official PHP
 * DOMDocument. These methods should not be implemented if you extend
 * DOMDocument. It is only for the IDE and syntactic analyzers to not generate
 * warnings in the interface.
 *
 * @see DOMDocument
 */
interface DOMDocumentInterface extends DOMParentNode
{
    /**
     * Returns the root element instance of the document.
     *
     * This is a safe way to access $documentElement when using this interface
     * as a variable type.
     *
     * If you do not use this, tools like phpstan will complain.
     *
     * @return DOMElement|null
     */
    public function getDocumentElement(): ?DOMElement;

    /**
     * Canonicalizes the XML according to the C14N specification.
     *
     * The official implementation of this interface inherits from DOMDocument
     * so this method is available. However, it is defined in the interface so
     * that tools like phpstan do not complain when using this interface as a
     * variable type.
     *
     * @param bool $exclusive Indicates if exclusive canonicalization is used.
     * @param bool $withComments Includes comments in the output if `true`.
     * @param array|null $xpath Optional list of nodes to include in the
     * canonicalization.
     * @param array|null $nsPrefixes Prefixes of namespaces to consider in the
     * canonicalization.
     * @return string|false A string with the canonicalized XML or `false` in
     * case of error.
     */
    public function C14N(
        bool $exclusive = false,
        bool $withComments = false,
        ?array $xpath = null,
        ?array $nsPrefixes = null
    ): string|false;

    /**
     * Creates a new element in the XML document.
     *
     * The official implementation of this interface inherits from DOMDocument
     * so this method is available. However, it is defined in the interface so
     * that tools like phpstan do not complain when using this interface as a
     * variable type.
     *
     * @param string $localName The local name of the element.
     * @param string $value The optional value of the element.
     * @return DOMElement|false The created element or `false` in case of error.
     */
    public function createElement(
        string $localName,
        string $value = ''
    ); // Adding a return type is incompatible with the official DOMDocument of PHP.

    /**
     * Creates a new element in a specific namespace.
     *
     * The official implementation of this interface inherits from DOMDocument
     * so this method is available. However, it is defined in the interface so
     * that tools like phpstan do not complain when using this interface as a
     * variable type.
     *
     * @param string|null $namespace The URI of the namespace (can be `null` for
     * none).
     * @param string $qualifiedName The qualified name of the element.
     * @param string $value The optional value of the element.
     * @return DOMElement|false The created element or `false` in case of error.
     */
    public function createElementNS(
        ?string $namespace,
        string $qualifiedName,
        string $value = ''
    ); // Adding a return type is incompatible with the official DOMDocument of PHP.

    /**
     * Validates the current XML document against an XML schema.
     *
     * The official implementation of this interface inherits from DOMDocument
     * so this method is available. However, it is defined in the interface so
     * that tools like phpstan do not complain when using this interface as a
     * variable type.
     *
     * @param string $filename The path to the schema file (.xsd).
     * @param int $flags Validation options (default `0`).
     * @return bool `true` if validation is successful, `false` in case of error.
     */
    public function schemaValidate(string $filename, int $flags = 0): bool;

    /**
     * Gets a list of nodes (DOMNodeList) using a tag name.
     *
     * Searches for all elements of the current document that match the
     * provided tag name (regardless of which part of the tree they are in).
     *
     * @param string $qualifiedName The qualified name of the tag to search for.
     * @return DOMNodeList A list of nodes that match the tag.
     */
    public function getElementsByTagName(string $qualifiedName): DOMNodeList;
}
