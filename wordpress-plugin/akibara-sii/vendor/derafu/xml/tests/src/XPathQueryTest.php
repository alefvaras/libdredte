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

use Derafu\Xml\XPathQuery;
use DOMDocument;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(XPathQuery::class)]
class XPathQueryTest extends TestCase
{
    private string $validXml;

    private string $invalidXml;

    private string $nestedXml;

    private string $xmlNamespaceAndParams;

    protected function setUp(): void
    {
        $this->validXml = <<<XML
            <root>
                <item id="1">First</item>
                <item id="2">Second</item>
                <item id="3">Third</item>
            </root>
        XML;

        $this->invalidXml = <<<XML
            <root>
                <item id="1">First</item>
                <item id="2">Second</item>
                <!-- Missing closing tag for root -->
        XML;
        $this->nestedXml = <<<XML
            <AUTORIZACION>
                <CAF>
                    <FRMA>firma_base64</FRMA>
                    <DA>
                        <TD>33</TD>
                        <RNG>
                            <D>1</D>
                            <H>100</H>
                        </RNG>
                    </DA>
                </CAF>
            </AUTORIZACION>
        XML;

        $this->xmlNamespaceAndParams = <<<XML
        <DTE xmlns="http://www.sii.cl/SiiDte" version="1.0">
          <Documento ID="LibreDTE_76192083-9_T33F1">
            <Encabezado>
              <IdDoc>
                <TipoDTE>33</TipoDTE>
                <Folio>1</Folio>
                <FchEmis>2025-01-03</FchEmis>
              </IdDoc>
              <Emisor>
                <RUTEmisor>76192083-9</RUTEmisor>
                <RznSoc>SASCO SpA</RznSoc>
              </Emisor>
            </Encabezado>
          </Documento>
        </DTE>
        XML;
    }

    public function testGetSingleValue(): void
    {
        $query = new XPathQuery($this->validXml);
        $result = $query->getValue('/root/item[@id="1"]');

        $this->assertSame('First', $result);
    }

    public function testGetMultipleValues(): void
    {
        $query = new XPathQuery($this->validXml);
        $results = $query->getValues('/root/item');

        $this->assertCount(3, $results);
        $this->assertSame(['First', 'Second', 'Third'], $results);
    }

    public function testGetNodes(): void
    {
        $query = new XPathQuery($this->validXml);
        $nodes = $query->getNodes('/root/item');

        $this->assertSame(3, $nodes->length);
        $this->assertSame('First', $nodes->item(0)->nodeValue);
        $this->assertSame('Second', $nodes->item(1)->nodeValue);
        $this->assertSame('Third', $nodes->item(2)->nodeValue);
    }

    public function testGetSingleResultWithGet(): void
    {
        $query = new XPathQuery($this->validXml);
        $result = $query->get('/root/item[@id="2"]');

        $this->assertSame('Second', $result);
    }

    public function testGetMultipleResultsWithGet(): void
    {
        $query = new XPathQuery($this->validXml);
        $results = $query->get('/root/item');

        $this->assertSame(['First', 'Second', 'Third'], $results);
    }

    public function testGetNullForNoMatch(): void
    {
        $query = new XPathQuery($this->validXml);
        $result = $query->get('/root/nonexistent');

        $this->assertNull($result);
    }

    public function testInvalidXPathThrowsException(): void
    {
        $query = new XPathQuery($this->validXml);

        $this->expectException(InvalidArgumentException::class);
        $query->getNodes('//root@invalid_xpath]');
    }

    public function testLoadMalformedXmlThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new XPathQuery($this->invalidXml);
    }

    public function testGetDomDocument(): void
    {
        $query = new XPathQuery($this->validXml);
        $dom = $query->getDomDocument();

        $this->assertInstanceOf(DOMDocument::class, $dom);
        $this->assertSame('root', $dom->documentElement->nodeName);
    }

    public function testGetSingleNode(): void
    {
        $query = new XPathQuery($this->nestedXml);
        $result = $query->get('//CAF/FRMA');

        $this->assertSame(
            'firma_base64',
            $result,
            'Debe devolver el valor del nodo FRMA.'
        );
    }

    public function testGetComplexStructure(): void
    {
        $query = new XPathQuery($this->nestedXml);
        $result = $query->get('/AUTORIZACION/CAF');

        $expected = [
            'FRMA' => 'firma_base64',
            'DA' => [
                'TD' => '33',
                'RNG' => [
                    'D' => '1',
                    'H' => '100',
                ],
            ],
        ];

        $this->assertSame(
            $expected,
            $result,
            'Debe devolver la estructura jerárquica completa de CAF.'
        );
    }

    public function testGetArrayOfNodes(): void
    {
        $xml = <<<XML
            <ROOT>
                <ITEM>Value1</ITEM>
                <ITEM>Value2</ITEM>
                <ITEM>Value3</ITEM>
            </ROOT>
        XML;

        $query = new XPathQuery($xml);
        $result = $query->get('//ITEM');

        $expected = ['Value1', 'Value2', 'Value3'];

        $this->assertSame(
            $expected,
            $result,
            'Debe devolver un arreglo de valores para nodos repetidos.'
        );
    }

    public function testGetWithInvalidXPath(): void
    {
        $query = new XPathQuery($this->nestedXml);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'An error occurred while executing the XPath expression'
        );

        $query->get('//Invalid@[XPath]');
    }

    public function testGetNonexistentNode(): void
    {
        $query = new XPathQuery($this->nestedXml);
        $result = $query->get('//NonexistentNode');

        $this->assertNull($result, 'Debe devolver null si el nodo no existe.');
    }

    public function testNamespaceHandling(): void
    {
        $query = new XPathQuery(
            $this->xmlNamespaceAndParams,
            ['ns' => 'http://www.sii.cl/SiiDte']
        );

        $result = $query->get('//ns:Encabezado/ns:Emisor/ns:RUTEmisor');
        $this->assertSame('76192083-9', $result);

        $result = $query->get('//ns:Encabezado/ns:IdDoc/ns:TipoDTE');
        $this->assertSame('33', $result);
    }

    public function testQueryWithParameters(): void
    {
        $query = new XPathQuery(
            $this->xmlNamespaceAndParams,
            ['ns' => 'http://www.sii.cl/SiiDte']
        );

        $params = ['tipo' => '33', 'folio' => '1'];
        $result = $query->get('//ns:IdDoc[ns:TipoDTE=:tipo and ns:Folio=:folio]', $params);

        $this->assertNotNull($result);
        $expected = ['TipoDTE' => '33', 'Folio' => '1', 'FchEmis' => '2025-01-03'];
        $this->assertSame($expected, $result);
    }

    public function testInvalidQueryThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $query = new XPathQuery(
            $this->xmlNamespaceAndParams,
            ['ns' => 'http://www.sii.cl/SiiDte']
        );
        $query->get('//invalid[');
    }

    public function testComplexStructureWithNamespaces(): void
    {
        $query = new XPathQuery(
            $this->xmlNamespaceAndParams,
            ['ns' => 'http://www.sii.cl/SiiDte']
        );

        $result = $query->get('//ns:Encabezado');
        $this->assertIsArray($result);
        $this->assertArrayHasKey('IdDoc', $result);
        $this->assertArrayHasKey('Emisor', $result);
    }

    public function testComplexStructureWithNamespacesDisabledAbsoluteNode(): void
    {
        $query = new XPathQuery($this->xmlNamespaceAndParams);

        $result = $query->get('/DTE/Documento/Encabezado');
        $this->assertIsArray($result);
        $this->assertArrayHasKey('IdDoc', $result);
        $this->assertArrayHasKey('Emisor', $result);
    }

    public function testComplexStructureWithNamespacesDisabledRelativeNode(): void
    {
        $query = new XPathQuery($this->xmlNamespaceAndParams);

        $result = $query->get('//Encabezado');
        $this->assertIsArray($result);
        $this->assertArrayHasKey('IdDoc', $result);
        $this->assertArrayHasKey('Emisor', $result);
    }

    public function testInvalidXmlThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new XPathQuery('<invalid><xml>', ['ns' => 'http://www.sii.cl/SiiDte']);
    }

    public function testGetDomDocumentWithNamespace(): void
    {
        $query = new XPathQuery(
            $this->xmlNamespaceAndParams,
            ['ns' => 'http://www.sii.cl/SiiDte']
        );
        $dom = $query->getDomDocument();

        $this->assertInstanceOf(DOMDocument::class, $dom);
        $this->assertStringContainsString('DTE', $dom->saveXml());
    }

    // Caso con comillas simples en el valor (valor coincide con el nodo XML).
    public function testResolveQueryWithEscapedValuesSimpleQuotes(): void
    {
        $xmlContent = <<<XML
        <DTE xmlns="http://www.sii.cl/SiiDte" version="1.0">
            <Documento ID="LibreDTE_76192083-9_T33F1">
                <Encabezado>
                    <Emisor>
                        <RUTEmisor>76192083-9</RUTEmisor>
                        <RznSoc>SASCO 'Testing' SpA</RznSoc>
                    </Emisor>
                </Encabezado>
            </Documento>
        </DTE>
        XML;

        $query = new XPathQuery(
            $xmlContent,
            ['ns' => 'http://www.sii.cl/SiiDte']
        );
        $result = $query->get(
            '//ns:Emisor/ns:RznSoc[text()=:value]',
            ['value' => "SASCO 'Testing' SpA"]
        );
        $this->assertSame("SASCO 'Testing' SpA", $result);
    }

    // Caso con comillas dobles en el valor.
    public function testResolveQueryWithEscapedValuesDobuleQuotes(): void
    {
        $xmlContent = <<<XML
        <DTE xmlns="http://www.sii.cl/SiiDte" version="1.0">
            <Documento ID="LibreDTE_76192083-9_T33F1">
                <Encabezado>
                    <Emisor>
                        <RUTEmisor>76192083-9</RUTEmisor>
                        <RznSoc>SASCO "Testing" SpA</RznSoc>
                    </Emisor>
                </Encabezado>
            </Documento>
        </DTE>
        XML;

        $query = new XPathQuery(
            $xmlContent,
            ['ns' => 'http://www.sii.cl/SiiDte']
        );
        $result = $query->get(
            '//ns:Emisor/ns:RznSoc[text()=:value]',
            ['value' => 'SASCO "Testing" SpA']
        );
        $this->assertSame('SASCO "Testing" SpA', $result);
    }

    // Caso con comillas simples y dobles mezcladas.
    public function testResolveQueryWithEscapedValuesMixedQuotes(): void
    {
        $xmlContent = <<<XML
        <DTE xmlns="http://www.sii.cl/SiiDte" version="1.0">
            <Documento ID="LibreDTE_76192083-9_T33F1">
                <Encabezado>
                    <Emisor>
                        <RUTEmisor>76192083-9</RUTEmisor>
                        <RznSoc>SASCO 'Mixed' "Testing" SpA</RznSoc>
                    </Emisor>
                </Encabezado>
            </Documento>
        </DTE>
        XML;

        $query = new XPathQuery(
            $xmlContent,
            ['ns' => 'http://www.sii.cl/SiiDte']
        );
        $result = $query->get(
            '//ns:Emisor/ns:RznSoc[text()=:value]',
            ['value' => 'SASCO \'Mixed\' "Testing" SpA']
        );
        $this->assertSame('SASCO \'Mixed\' "Testing" SpA', $result);
    }

    // Caso sin comillas (valor básico).
    public function testResolveQueryWithEscapedValuesWithoutQuotes(): void
    {
        $xmlContent = <<<XML
        <DTE xmlns="http://www.sii.cl/SiiDte" version="1.0">
            <Documento ID="LibreDTE_76192083-9_T33F1">
                <Encabezado>
                    <Emisor>
                        <RUTEmisor>76192083-9</RUTEmisor>
                        <RznSoc>SASCO Testing SpA</RznSoc>
                    </Emisor>
                </Encabezado>
            </Documento>
        </DTE>
        XML;

        $query = new XPathQuery(
            $xmlContent,
            ['ns' => 'http://www.sii.cl/SiiDte']
        );
        $result = $query->get(
            '//ns:Emisor/ns:RznSoc[text()=:value]',
            ['value' => 'SASCO Testing SpA']
        );
        $this->assertSame('SASCO Testing SpA', $result);
    }

    public function testQueryWithContextNode(): void
    {
        $query = new XPathQuery(
            $this->xmlNamespaceAndParams,
            ['ns' => 'http://www.sii.cl/SiiDte']
        );

        // Obtener el nodo 'Encabezado' como contexto
        $contextNode = $query->getNodes('//ns:Encabezado')->item(0);

        // Consultar desde el contexto del nodo 'Encabezado'
        $result = $query->get('ns:IdDoc/ns:TipoDTE', contextNode: $contextNode);
        $this->assertSame('33', $result);

        $result = $query->get('ns:IdDoc/ns:Folio', contextNode: $contextNode);
        $this->assertSame('1', $result);
    }

    public function testQueryWithContextNodeNoResult(): void
    {
        $query = new XPathQuery(
            $this->xmlNamespaceAndParams,
            ['ns' => 'http://www.sii.cl/SiiDte']
        );

        // Obtener el nodo 'Encabezado' como contexto
        $contextNode = $query->getNodes('//ns:Encabezado')->item(0);

        // Intentar consultar un nodo que no existe desde el contexto
        $result = $query->get('ns:NonExistentNode', contextNode: $contextNode);
        $this->assertNull($result);
    }

    public function testQueryWithContextNodeOnMultipleMatches(): void
    {
        $query = new XPathQuery(
            $this->xmlNamespaceAndParams,
            ['ns' => 'http://www.sii.cl/SiiDte']
        );

        // Obtener el nodo 'Encabezado' como contexto
        $contextNode = $query->getNodes('//ns:Encabezado')->item(0);

        // Consultar varios nodos desde el contexto
        $result = $query->getValues('ns:IdDoc/ns:*', contextNode: $contextNode);
        $this->assertCount(3, $result); // Hay 3 nodos en 'IdDoc': TipoDTE, Folio y FchEmis.
        $this->assertContains('33', $result);
        $this->assertContains('1', $result);
    }

    public function testXmlWithMultipleNodesWithTheSameName(): void
    {
        $xmlContent = <<<XML
        <DTE xmlns="http://www.sii.cl/SiiDte" version="1.0">
            <Documento ID="LibreDTE_76192083-9_T33F1">
                <Encabezado>
                    <Emisor>
                        <RUTEmisor>76192083-9</RUTEmisor>
                        <RznSoc>SASCO Testing SpA</RznSoc>
                    </Emisor>
                </Encabezado>
                <Detalle>
                    <NroLinDet>1</NroLinDet>
                    <NmbItem>Producto A</NmbItem>
                    <QtyItem>2</QtyItem>
                    <PrcItem>1000</PrcItem>
                    <MontoItem>2000</MontoItem>
                </Detalle>
                <Detalle>
                    <NroLinDet>2</NroLinDet>
                    <NmbItem>Producto B</NmbItem>
                    <QtyItem>1</QtyItem>
                    <PrcItem>1500</PrcItem>
                    <MontoItem>1500</MontoItem>
                </Detalle>
                <Detalle>
                    <NroLinDet>3</NroLinDet>
                    <NmbItem>Producto C</NmbItem>
                    <QtyItem>3</QtyItem>
                    <PrcItem>500</PrcItem>
                    <MontoItem>1500</MontoItem>
                </Detalle>
            </Documento>
        </DTE>
        XML;

        $query = new XPathQuery(
            $xmlContent,
            ['ns' => 'http://www.sii.cl/SiiDte']
        );
        $result = $query->get('/');

        // Verificar que el resultado es un array.
        $this->assertIsArray($result);

        // Verificar que existe la estructura DTE > Documento > Detalle.
        $this->assertArrayHasKey('DTE', $result);
        $this->assertIsArray($result['DTE']);
        $this->assertArrayHasKey('Documento', (array) $result['DTE']);
        $this->assertIsArray($result['DTE']['Documento']);
        $this->assertArrayHasKey('Detalle', (array) $result['DTE']['Documento']);

        // Verificar que Detalle es un array con 3 elementos.
        $detalle = (array) $result['DTE']['Documento']['Detalle'];
        $this->assertIsArray($detalle);
        $this->assertCount(3, $detalle);

        // Verificar el contenido de cada nodo Detalle.
        $this->assertSame('1', $detalle[0]['NroLinDet']);
        $this->assertSame('Producto A', $detalle[0]['NmbItem']);
        $this->assertSame('2', $detalle[0]['QtyItem']);
        $this->assertSame('1000', $detalle[0]['PrcItem']);
        $this->assertSame('2000', $detalle[0]['MontoItem']);

        $this->assertSame('2', $detalle[1]['NroLinDet']);
        $this->assertSame('Producto B', $detalle[1]['NmbItem']);
        $this->assertSame('1', $detalle[1]['QtyItem']);
        $this->assertSame('1500', $detalle[1]['PrcItem']);
        $this->assertSame('1500', $detalle[1]['MontoItem']);

        $this->assertSame('3', $detalle[2]['NroLinDet']);
        $this->assertSame('Producto C', $detalle[2]['NmbItem']);
        $this->assertSame('3', $detalle[2]['QtyItem']);
        $this->assertSame('500', $detalle[2]['PrcItem']);
        $this->assertSame('1500', $detalle[2]['MontoItem']);
    }
}
