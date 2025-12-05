<?php

declare(strict_types=1);

/**
 * Derafu: XML - Library for XML manipulation.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\TestsXml;

use Derafu\Xml\Exception\XmlException;
use Derafu\Xml\XmlDocument;
use Derafu\Xml\XmlHelper;
use Derafu\Xml\XPathQuery;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(XmlDocument::class)]
#[CoversClass(XmlException::class)]
#[CoversClass(XmlHelper::class)]
#[CoversClass(XPathQuery::class)]
class XmlTest extends TestCase
{
    /**
     * Verifica que el documento XML se carga correctamente.
     */
    public function testXmlLoadXml(): void
    {
        $xmlContent = <<<XML
        <root>
            <element>Value</element>
        </root>
        XML;

        $doc = new XmlDocument();
        $result = $doc->loadXml($xmlContent);

        $this->assertTrue($result);
        $this->assertSame('root', $doc->documentElement->tagName);
    }

    /**
     * Verifica que se obtenga correctamente el nombre del tag raíz del
     * documento XML.
     */
    public function testXmlGetName(): void
    {
        $xmlContent = <<<XML
        <root>
            <element>Value</element>
        </root>
        XML;

        $doc = new XmlDocument();
        $doc->loadXml($xmlContent);

        $this->assertSame('root', $doc->getName());
    }

    /**
     * Verifica la obtención del espacio de nombres del documento XML cuando
     * existe.
     */
    public function testXmlGetNamespace(): void
    {
        $xmlContent = <<<XML
        <root xmlns="http://example.com">
            <element>Value</element>
        </root>
        XML;

        $doc = new XmlDocument();
        $doc->loadXml($xmlContent);

        $this->assertSame('http://example.com', $doc->getNamespace());
    }

    /**
     * Verifica la obtención del espacio de nombres del documento XML cuando
     * no existe.
     */
    public function testXmlGetNamespaceNull(): void
    {
        $xmlContent = <<<XML
        <root>
            <element>Value</element>
        </root>
        XML;

        $doc = new XmlDocument();
        $doc->loadXml($xmlContent);

        $this->assertNull($doc->getNamespace());
    }

    /**
     * Verifica la obtención del schema asociado al documento XML cuando
     * existe.
     */
    public function testXmlGetSchema(): void
    {
        $xmlContent = <<<XML
        <root xsi:schemaLocation="http://example.com schema.xsd"
              xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
            <element>Value</element>
        </root>
        XML;

        $doc = new XmlDocument();
        $doc->loadXml($xmlContent);

        $this->assertSame('schema.xsd', $doc->getSchema());
    }

    /**
     * Verifica la obtención del schema asociado al documento XML cuando no
     * existe.
     */
    public function testXmlGetSchemaNull(): void
    {
        $xmlContent = <<<XML
        <root>
            <element>Value</element>
        </root>
        XML;

        $doc = new XmlDocument();
        $doc->loadXml($xmlContent);

        $this->assertNull($doc->getSchema());
    }

    /**
     * Verifica que el método saveXml() genera correctamente el XML.
     */
    public function testXmlSaveXml(): void
    {
        $xmlContent = <<<XML
        <root>
            <element>Value</element>
        </root>
        XML;

        $doc = new XmlDocument();
        $doc->loadXml($xmlContent);

        $expectedXml = <<<XML
        <?xml version="1.0" encoding="ISO-8859-1"?>
        <root>
            <element>Value</element>
        </root>

        XML;

        $this->assertXmlStringEqualsXmlString($expectedXml, $doc->saveXml());
    }

    /**
     * Verifica que el método C14N() funcione correctamente, generando la
     * versión canónica del XML.
     */
    public function testXmlC14N(): void
    {
        $xmlContent = <<<XML
        <root>
            <element>Value</element>
        </root>
        XML;

        $doc = new XmlDocument();
        $doc->loadXml($xmlContent);

        $canonicalXml = $doc->C14N();

        $this->assertNotEmpty($canonicalXml);
        $expectedXml = "<root>\n    <element>Value</element>\n</root>";
        $this->assertStringContainsString($expectedXml, $canonicalXml);
    }

    /**
     * Verifica que el método C14NWithIso88591Encoding() aplane correctamente el
     * documento XML.
     */
    public function testXmlC14NWithIso88591Encoding(): void
    {
        $xmlContent = <<<XML
        <root>
            <element>Value</element>
        </root>
        XML;

        $doc = new XmlDocument();
        $doc->loadXml($xmlContent);

        $flattenedXml = $doc->C14NWithIso88591EncodingFlattened();

        $expectedXml = '<root><element>Value</element></root>';
        $this->assertSame($expectedXml, $flattenedXml);
    }

    /**
     * Verifica que C14NWithIso88591Encoding() funcione correctamente cuando se
     * proporciona una expresión XPath.
     */
    public function testXmlC14NWithIso88591EncodingWithXPath(): void
    {
        $xmlContent = <<<XML
        <root>
            <element>Value</element>
            <element2>Other Value</element2>
        </root>
        XML;

        $doc = new XmlDocument();
        $doc->loadXml($xmlContent);

        $flattenedXml = $doc->C14NWithIso88591Encoding('//element2');

        $expectedXml = '<element2>Other Value</element2>';
        $this->assertSame($expectedXml, $flattenedXml);
    }

    /**
     * Verifica que C14NWithIso88591Encoding() retorne false cuando la expresión
     * XPath no coincide con ningún nodo.
     */
    public function testXmlC14NWithIso88591EncodingXPathNotFound(): void
    {
        $this->expectException(XmlException::class);

        $xmlContent = <<<XML
        <root>
            <element>Value</element>
        </root>
        XML;

        $doc = new XmlDocument();
        $doc->loadXml($xmlContent);

        $xml = $doc->C14NWithIso88591Encoding('//nonexistent');
    }
}
