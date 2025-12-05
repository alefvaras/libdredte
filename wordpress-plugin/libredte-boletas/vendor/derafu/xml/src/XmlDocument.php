<?php

declare(strict_types=1);

/**
 * Derafu: XML - Library for XML manipulation.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Xml;

use Derafu\Xml\Contract\XmlDocumentInterface;
use Derafu\Xml\Exception\XmlException;
use DOMDocument;
use DOMElement;
use DOMNode;
use DOMNodeList;

/**
 * Class that represents an XML document.
 */
final class XmlDocument extends DOMDocument implements XmlDocumentInterface
{
    /**
     * Instance to facilitate XML handling using XPath.
     *
     * @var XPathQuery
     */
    private XPathQuery $xPathQuery;

    /**
     * XML representation as an array.
     *
     * @var array
     */
    private array $array;

    /**
     * Constructor of the XML document.
     *
     * @param string $version XML version.
     * @param string $encoding XML encoding.
     */
    public function __construct(
        string $version = '1.0',
        string $encoding = 'ISO-8859-1'
    ) {
        parent::__construct($version, $encoding);

        $this->formatOutput = true;
        $this->preserveWhiteSpace = true;
    }

    /**
     * {@inheritDoc}
     */
    public function getName(): string
    {
        return $this->documentElement->tagName;
    }

    /**
     * {@inheritDoc}
     */
    public function getNamespace(): ?string
    {
        $namespace = $this->documentElement->getAttribute('xmlns');

        return $namespace !== '' ? $namespace : null;
    }

    /**
     * {@inheritDoc}
     */
    public function getSchema(): ?string
    {
        $schemaLocation = $this->documentElement->getAttribute(
            'xsi:schemaLocation'
        );

        if (!$schemaLocation || !str_contains($schemaLocation, ' ')) {
            return null;
        }

        return explode(' ', $schemaLocation)[1];
    }

    /**
     * {@inheritDoc}
     */
    public function loadXml(string $source, int $options = 0): bool
    {
        // If there is no XML string in the source, then an exception is thrown.
        if (empty($source)) {
            throw new XmlException(
                'The XML content that you want to load is empty.'
            );
        }

        // Convert the XML if necessary.
        preg_match(
            '/<\?xml\s+version="([^"]+)"\s+encoding="([^"]+)"\?>/',
            $source,
            $matches
        );
        //$version = $matches[1] ?? $this->xmlVersion;
        $encoding = strtoupper($matches[2] ?? $this->encoding);
        if ($encoding === 'UTF-8' && $this->encoding === 'ISO-8859-1') {
            $source = mb_convert_encoding($source, 'ISO-8859-1', 'UTF-8');
            $source = str_replace(
                ' encoding="UTF-8"?>',
                ' encoding="ISO-8859-1"?>',
                $source
            );
        }

        // If the XML that will be loaded does not start with the XML tag, it
        // is added. This is 100% necessary because if it comes in a different
        // encoding to UTF-8 (the most normal) and does not come with this tag
        // opening the XML, it will complain that the encoding is missing when
        // loading.
        if (!str_starts_with($source, '<?xml')) {
            $source = '<?xml version="1.0" encoding="' . $encoding . '"?>'
                . "\n" . $source
            ;
        }

        // Get the current state of libxml and change it before loading the XML
        // to get the errors in a variable if something fails.
        $useInternalErrors = libxml_use_internal_errors(true);

        // Load the XML.
        $status = parent::loadXml($source, $options);

        // Get errors, clear them and restore the libxml error state.
        $errors = libxml_get_errors();
        libxml_clear_errors();
        libxml_use_internal_errors($useInternalErrors);

        if (!$status) {
            throw new XmlException('Error al cargar el XML.', $errors);
        }

        // Return the status of the XML loading.
        // It will only return `true`, since if it fails an exception is thrown.
        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function saveXml(?DOMNode $node = null, int $options = 0): string
    {
        $xml = parent::saveXml($node, $options);

        return XmlHelper::fixEntities($xml);
    }

    /**
     * {@inheritDoc}
     */
    public function getXml(): string
    {
        $xml = $this->saveXml();
        $xml = preg_replace(
            '/<\?xml\s+version="1\.0"\s+encoding="[^"]+"\s*\?>/i',
            '',
            $xml
        );

        return trim($xml);
    }

    /**
     * {@inheritDoc}
     */
    public function C14NWithIso88591Encoding(?string $xpath = null): string
    {
        // If an XPath is provided, filter the nodes.
        if ($xpath) {
            $node = $this->getNodes($xpath)->item(0);
            if (!$node) {
                throw new XmlException(sprintf(
                    'No fue posible obtener el nodo con el XPath %s.',
                    $xpath
                ));
            }
            $xml = $node->C14N();
        }
        // Use C14N() for the entire document if no XPath is specified.
        else {
            $xml = $this->C14N();
        }

        // Fix XML entities.
        $xml = XmlHelper::fixEntities($xml);

        // Convert the flattened XML from UTF-8 to ISO-8859-1.
        // Required because C14N() always delivers data in UTF-8.
        $xml = mb_convert_encoding($xml, 'ISO-8859-1', 'UTF-8');

        // Return the canonicalized XML.
        return $xml;
    }

    /**
     * {@inheritDoc}
     */
    public function C14NWithIso88591EncodingFlattened(?string $xpath = null): string
    {
        // Get the canonicalized XML encoded in ISO8859-1.
        $xml = $this->C14NWithIso88591Encoding($xpath);

        // Remove the spaces between tags.
        $xml = preg_replace("/>\s+</", '><', $xml);

        // Return the canonicalized and flattened XML.
        return $xml;
    }

    /**
     * {@inheritDoc}
     */
    public function getSignatureNodeXml(): ?string
    {
        $tag = $this->documentElement->tagName;
        $xpath = '/' . $tag . '/Signature';
        $signatureElement = $this->getNodes($xpath)->item(0);

        return $signatureElement?->C14N();
    }

    /**
     * {@inheritDoc}
     */
    public function query(string $query, array $params = []): string|array|null
    {
        if (!isset($this->xPathQuery)) {
            $this->xPathQuery = new XPathQuery($this);
        }

        return $this->xPathQuery->get($query, $params);
    }

    /**
     * {@inheritDoc}
     */
    public function getNodes(string $query, array $params = []): DOMNodeList
    {
        if (!isset($this->xPathQuery)) {
            $this->xPathQuery = new XPathQuery($this);
        }

        return $this->xPathQuery->getNodes($query, $params);
    }

    /**
     * {@inheritDoc}
     */
    public function get(string $selector, mixed $default = null): mixed
    {
        $array = $this->toArray();

        $keys = explode('.', $selector);
        $current = $array;

        foreach ($keys as $key) {
            if (!is_array($current) || !array_key_exists($key, $current)) {
                return $default;
            }
            $current = $current[$key];
        }

        return $current;
    }

    /**
     * {@inheritDoc}
     */
    public function toArray(): array
    {
        if (!isset($this->array)) {
            $this->array = $this->query('/');
        }

        return $this->array;
    }

    /**
     * {@inheritDoc}
     */
    public function getDocumentElement(): ?DOMElement
    {
        return $this->documentElement;
    }

    /**
     * {@inheritDoc}
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
