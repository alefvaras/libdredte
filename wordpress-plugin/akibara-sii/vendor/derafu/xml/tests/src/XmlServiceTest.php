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

use Derafu\Xml\Contract\XmlServiceInterface;
use Derafu\Xml\Exception\XmlException;
use Derafu\Xml\Service\XmlDecoder;
use Derafu\Xml\Service\XmlEncoder;
use Derafu\Xml\Service\XmlService;
use Derafu\Xml\Service\XmlValidator;
use Derafu\Xml\XmlDocument;
use Derafu\Xml\XmlHelper;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(XmlService::class)]
#[CoversClass(XmlDocument::class)]
#[CoversClass(XmlException::class)]
#[CoversClass(XmlDecoder::class)]
#[CoversClass(XmlEncoder::class)]
#[CoversClass(XmlValidator::class)]
#[CoversClass(XmlHelper::class)]
class XmlServiceTest extends TestCase
{
    /**
     * Servicio de documentos XML.
     *
     * @var XmlServiceInterface
     */
    private XmlServiceInterface $xmlService;

    /**
     * Atributo con los casos para cada test.
     *
     * @var array<string, array>
     */
    private static array $testCases;

    /**
     * Inicializa cada test.
     */
    protected function setUp(): void
    {
        $encoder = new XmlEncoder();
        $decoder = new XmlDecoder();
        $validator = new XmlValidator();

        $this->xmlService = new XmlService($encoder, $decoder, $validator);
    }

    /**
     * Entrega los casos según el nombre del test.
     *
     * Este método lo utiliza cada proveedor de datos para obtener los datos de
     * los tests.
     *
     * @return array<string, array>
     */
    private static function dataProvider(string $testName): array
    {
        if (!isset(self::$testCases)) {
            $testCasesFile = __DIR__ . '/../fixtures/encode_and_decode.php';
            self::$testCases = require $testCasesFile;
        }

        if (!isset(self::$testCases[$testName])) {
            self::fail(sprintf(
                'El test %s() no tiene casos asociados en el dataProvider().',
                $testName
            ));
        }

        return self::$testCases[$testName];
    }

    public static function arrayToXmlAndBackToArrayDataProvider(): array
    {
        return self::dataProvider('testArrayToXmlAndBackToArray');
    }

    public static function arrayToXmlSaveXmlDataProvider(): array
    {
        return self::dataProvider('testArrayToXmlSaveXml');
    }

    public static function arrayToXmlC14NDataProvider(): array
    {
        return self::dataProvider('testArrayToXmlC14N');
    }

    public static function arrayToXmlC14NWithIso88591EncodingDataProvider(): array
    {
        return self::dataProvider('testArrayToXmlC14NWithIso88591Encoding');
    }

    public static function xmlToArrayDataProvider(): array
    {
        return self::dataProvider('testXmlToArray');
    }

    public static function xmlToSaveXmlDataProvider(): array
    {
        return self::dataProvider('testXmlToSaveXml');
    }

    public static function xmlToC14NDataProvider(): array
    {
        return self::dataProvider('testXmlToC14N');
    }

    public static function xmlToC14NWithIso88591EncodingDataProvider(): array
    {
        return self::dataProvider('testXmlToC14NWithIso88591Encoding');
    }

    /**
     * Convierte un arreglo a un Xml y luego de vuelta a un arreglo,
     * asegurando que la estructura original se mantiene.
     */
    #[DataProvider('arrayToXmlAndBackToArrayDataProvider')]
    public function testArrayToXmlAndBackToArray(
        array $data,
        array $expected,
        ?string $expectedException
    ): void {
        if ($expectedException) {
            $this->expectException($expectedException);
        }

        $xml = $this->xmlService->encode($data);
        $arrayData = $this->xmlService->decode($xml);

        // Validar estructura
        $this->assertSame($expected, $arrayData);

        // Validar codificación en cada valor del arreglo
        $this->assertArrayEncoding($arrayData, 'UTF-8');
    }

    /**
     * Convierte un arreglo a un Xml y lo guarda como un string XML
     * con saveXml(), asegurando que la codificación y contenido son correctos.
     */
    #[DataProvider('arrayToXmlSaveXmlDataProvider')]
    public function testArrayToXmlSaveXml(
        array $data,
        string $expected,
        ?string $expectedException
    ): void {
        if ($expectedException) {
            $this->expectException($expectedException);
        }

        $xml = $this->xmlService->encode($data);
        $xmlString = $xml->saveXml();

        // Validar contenido.
        $this->assertSame($expected, $xmlString);

        // Validar codificación.
        $this->assertSame(
            'ISO-8859-1',
            mb_detect_encoding($xmlString, 'ISO-8859-1', true)
        );
    }

    /**
     * Convierte un arreglo a un Xml y lo guarda como un string XML
     * con C14N(), asegurando que el contenido sea correcto.
     */
    #[DataProvider('arrayToXmlC14NDataProvider')]
    public function testArrayToXmlC14N(
        array $data,
        string $expected,
        ?string $expectedException
    ): void {
        if ($expectedException) {
            $this->expectException($expectedException);
        }

        $xml = $this->xmlService->encode($data);
        $xmlString = $xml->C14N();
        $xmlString = XmlHelper::fixEntities($xmlString);

        // Validar contenido.
        $this->assertSame($expected, $xmlString);
    }

    /**
     * Convierte un arreglo a un Xml y lo guarda como un string XML
     * con testArrayToXmlC14NWithIso88591Encoding(), asegurando que la codificación
     * y contenido son correctos.
     */
    #[DataProvider('arrayToXmlC14NWithIso88591EncodingDataProvider')]
    public function testArrayToXmlC14NWithIso88591Encoding(
        array $data,
        string $expected,
        ?string $expectedException
    ): void {
        if ($expectedException) {
            $this->expectException($expectedException);
        }

        $xml = $this->xmlService->encode($data);
        $xmlString = $xml->C14NWithIso88591Encoding();

        // Validar contenido.
        $this->assertSame($expected, $xmlString);

        // Validar codificación.
        $this->assertSame(
            'ISO-8859-1',
            mb_detect_encoding($xmlString, 'ISO-8859-1', true)
        );
    }

    /**
     * Convierte un string XML a un Xml y luego a un arreglo,
     * asegurando que la estructura se mantiene y los datos están en la
     * codificación correcta.
     */
    #[DataProvider('xmlToArrayDataProvider')]
    public function testXmlToArray(
        string $xmlContent,
        array $expected,
        ?string $expectedException
    ): void {
        if ($expectedException) {
            $this->expectException($expectedException);
        }

        $doc = new XmlDocument();
        $doc->loadXml($xmlContent);
        $arrayData = $this->xmlService->decode($doc);

        // Validar estructura.
        $this->assertSame($expected, $arrayData);

        // Validar codificación en cada valor del arreglo.
        $this->assertArrayEncoding($arrayData, 'UTF-8');
    }

    /**
     * Convierte un string XML a un Xml y lo guarda como un string XML
     * con saveXml(), asegurando que la codificación y contenido son correctos.
     */
    #[DataProvider('xmlToSaveXmlDataProvider')]
    public function testXmlToSaveXml(
        string $xmlContent,
        string $expected,
        ?string $expectedException
    ): void {
        if ($expectedException) {
            $this->expectException($expectedException);
        }

        $doc = new XmlDocument();
        $doc->loadXml($xmlContent);
        $xmlString = $doc->saveXml();

        // Validar contenido.
        $this->assertSame($expected, $xmlString);

        // Validar codificación.
        $this->assertSame(
            'ISO-8859-1',
            mb_detect_encoding($xmlString, 'ISO-8859-1', true)
        );
    }

    /**
     * Convierte un string XML a un Xml y lo guarda como un string XML
     * con C14N(), asegurando que el contenido sea correcto.
     */
    #[DataProvider('xmlToC14NDataProvider')]
    public function testXmlToC14N(
        string $xmlContent,
        string $expected,
        ?string $expectedException
    ): void {
        if ($expectedException) {
            $this->expectException($expectedException);
        }

        $doc = new XmlDocument();
        $doc->loadXml($xmlContent);
        $xmlString = $doc->C14N();
        $xmlString = XmlHelper::fixEntities($xmlString);

        // Validar contenido.
        $this->assertSame($expected, $xmlString);
    }

    /**
     * Convierte un string XML a un Xml y lo guarda como un string XML
     * con C14NWithIso88591Encoding(), asegurando que la codificación y contenido
     * son correctos.
     */
    #[DataProvider('xmlToC14NWithIso88591EncodingDataProvider')]
    public function testXmlToC14NWithIso88591Encoding(
        string $xmlContent,
        string $expected,
        ?string $expectedException
    ): void {
        if ($expectedException) {
            $this->expectException($expectedException);
        }

        $doc = new XmlDocument();
        $doc->loadXml($xmlContent);
        $xmlString = $doc->C14NWithIso88591Encoding();

        // Validar contenido.
        $this->assertSame($expected, $xmlString);

        // Validar codificación.
        $this->assertSame(
            'ISO-8859-1',
            mb_detect_encoding($xmlString, 'ISO-8859-1', true)
        );
    }

    /**
     * Función auxiliar para verificar la codificación de los valores en un
     * arreglo.
     *
     * @param array $data Arreglo que contiene los valores a verificar.
     * @param string $expectedEncoding Codificación esperada (ejemplo: UTF-8).
     */
    private function assertArrayEncoding(
        array $data,
        string $expectedEncoding
    ): void {
        array_walk_recursive($data, function ($item) use ($expectedEncoding) {
            if (is_string($item)) {
                $this->assertSame(
                    $expectedEncoding,
                    mb_detect_encoding($item, $expectedEncoding, true)
                );
            }
        });
    }

    /**
     * Verifica que la validación del XML contra el esquema pase correctamente.
     */
    public function testValidateSchemaSuccess(): void
    {
        // Crear el esquema XML (XSD).
        $xsdSchema = <<<XSD
        <?xml version="1.0" encoding="UTF-8"?>
        <xs:schema xmlns:xs="http://www.w3.org/2001/XMLSchema">
            <xs:element name="root">
                <xs:complexType>
                    <xs:sequence>
                        <xs:element name="element" type="xs:string"/>
                    </xs:sequence>
                </xs:complexType>
            </xs:element>
        </xs:schema>
        XSD;

        // Guardar el esquema en un archivo temporal.
        $schemaPath = tempnam(sys_get_temp_dir(), 'schema') . '.xsd';
        file_put_contents($schemaPath, $xsdSchema);

        // Crear un XML válido según el esquema.
        $xmlContent = <<<XML
        <root>
            <element>Value</element>
        </root>
        XML;

        // Cargar el XML en un Xml.
        $xml = new XmlDocument();
        $xml->loadXml($xmlContent);

        // Validar el XML contra el esquema.
        try {
            $this->xmlService->validate($xml, $schemaPath);
            $this->assertTrue(true); // Sin excepción la validación pasó.
        } catch (XmlException $e) {
            $message = sprintf(
                'La validación del XML no debería fallar, pero ocurrió un error: %s',
                $e->getMessage()
            );
            $this->fail($message);
        }

        // Eliminar el archivo temporal del esquema.
        unlink($schemaPath);
    }

    /**
     * Verifica que la validación del XML contra el esquema falle correctamente.
     */
    public function testValidateSchemaFailure(): void
    {
        // Crear el esquema XML (XSD).
        $xsdSchema = <<<XSD
        <?xml version="1.0" encoding="UTF-8"?>
        <xs:schema xmlns:xs="http://www.w3.org/2001/XMLSchema">
            <xs:element name="root">
                <xs:complexType>
                    <xs:sequence>
                        <xs:element name="element" type="xs:string"/>
                    </xs:sequence>
                </xs:complexType>
            </xs:element>
        </xs:schema>
        XSD;

        // Guardar el esquema en un archivo temporal.
        $schemaPath = tempnam(sys_get_temp_dir(), 'schema') . '.xsd';
        file_put_contents($schemaPath, $xsdSchema);

        // Crear un XML inválido según el esquema.
        $invalidXmlContent = <<<XML
        <root>
            <wrongElement>Value</wrongElement>
        </root>
        XML;

        // Cargar el XML en un Xml.
        $xml = new XmlDocument();
        $xml->loadXml($invalidXmlContent);

        // Validar el XML contra el esquema y esperar que falle.
        $this->expectException(XmlException::class);
        $this->xmlService->validate($xml);

        // Eliminar el archivo temporal del esquema.
        unlink($schemaPath);
    }
}
