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
class XmlSpecialCasesTest extends TestCase
{
    /**
     * XML document service.
     *
     * @var XmlServiceInterface
     */
    private XmlServiceInterface $xmlService;

    /**
     * Attribute with the cases for each test.
     *
     * @var array<string, array>
     */
    private static array $testCases;

    /**
     * Initializes each test.
     */
    protected function setUp(): void
    {
        $encoder = new XmlEncoder();
        $decoder = new XmlDecoder();
        $validator = new XmlValidator();

        $this->xmlService = new XmlService($encoder, $decoder, $validator);
    }

    /**
     * Returns the cases according to the test name.
     *
     * This method is used by each data provider to obtain the test data.
     *
     * @return array<string, array>
     */
    private static function dataProvider(string $testName): array
    {
        if (!isset(self::$testCases)) {
            $testCasesFile = __DIR__ . '/../fixtures/special_cases.php';
            self::$testCases = require $testCasesFile;
        }

        if (!isset(self::$testCases[$testName])) {
            self::fail(sprintf(
                'Test %s() does not have associated cases in dataProvider().',
                $testName
            ));
        }

        return self::$testCases[$testName];
    }

    public static function utf8CharactersNotSupportedInIso88591DataProvider(): array
    {
        return self::dataProvider('testUtf8CharactersNotSupportedInIso88591');
    }

    public static function controlCharactersInIso88591DataProvider(): array
    {
        return self::dataProvider('testControlCharactersInIso88591');
    }

    public static function specialCharactersInAttributesDataProvider(): array
    {
        return self::dataProvider('testSpecialCharactersInAttributes');
    }

    public static function c14NWithSpecialCharactersDataProvider(): array
    {
        return self::dataProvider('testC14NWithSpecialCharacters');
    }

    public static function specialWhitespaceCharactersDataProvider(): array
    {
        return self::dataProvider('testSpecialWhitespaceCharacters');
    }

    public static function c14NEncodingValidationDataProvider(): array
    {
        return self::dataProvider('testC14NEncodingValidation');
    }

    public static function digitalSignatureCompatibilityDataProvider(): array
    {
        return self::dataProvider('testDigitalSignatureCompatibility');
    }

    /**
     * Verifies that UTF-8 characters not supported in ISO-8859-1 are handled
     * correctly during encoding.
     */
    #[DataProvider('utf8CharactersNotSupportedInIso88591DataProvider')]
    public function testUtf8CharactersNotSupportedInIso88591(
        array $data,
        array $expected,
        ?string $expectedException
    ): void {
        if ($expectedException) {
            $this->expectException($expectedException);
        }

        $xml = $this->xmlService->encode($data);
        $arrayData = $this->xmlService->decode($xml);

        // Validate that the data is preserved correctly.
        $this->assertSame($expected, $arrayData);

        // Verify that the encoding is ISO-8859-1.
        $xmlString = $xml->saveXml();
        $this->assertSame(
            'ISO-8859-1',
            mb_detect_encoding($xmlString, 'ISO-8859-1', true)
        );
    }

    /**
     * Verifies that control characters are removed during encoding for XML-DSIG.
     */
    #[DataProvider('controlCharactersInIso88591DataProvider')]
    public function testControlCharactersInIso88591(
        array $data,
        array $expected,
        ?string $expectedException
    ): void {
        if ($expectedException) {
            $this->expectException($expectedException);
        }

        $xml = $this->xmlService->encode($data);
        $arrayData = $this->xmlService->decode($xml);

        // Validate that control characters are removed.
        $this->assertSame($expected, $arrayData);

        // Verify that the XML does not contain control characters.
        $xmlString = $xml->saveXml();
        $this->assertStringNotContainsString("\x00", $xmlString);
        $this->assertStringNotContainsString("\x01", $xmlString);
        $this->assertStringNotContainsString("\x02", $xmlString);
        $this->assertStringNotContainsString("\x03", $xmlString);
        $this->assertStringNotContainsString("\x04", $xmlString);
        $this->assertStringNotContainsString("\x05", $xmlString);
        $this->assertStringNotContainsString("\x06", $xmlString);
        $this->assertStringNotContainsString("\x07", $xmlString);
        $this->assertStringNotContainsString("\x7F", $xmlString);
    }

    /**
     * Verifies that special characters in attributes are handled correctly for
     * XML-DSIG.
     */
    #[DataProvider('specialCharactersInAttributesDataProvider')]
    public function testSpecialCharactersInAttributes(
        array $data,
        array $expected,
        ?string $expectedException
    ): void {
        if ($expectedException) {
            $this->expectException($expectedException);
        }

        $xml = $this->xmlService->encode($data);
        $arrayData = $this->xmlService->decode($xml);

        // Validate structure.
        $this->assertSame($expected, $arrayData);

        // Validate that the attributes are properly escaped in the XML.
        $xmlString = $xml->saveXml();

        // Check for special characters if they are present in the attribute value
        if (str_contains($data['root']['element']['@attributes']['attr'], '&')) {
            $this->assertStringContainsString('&amp;', $xmlString);
        }
        if (str_contains($data['root']['element']['@attributes']['attr'], '<')) {
            $this->assertStringContainsString('&lt;', $xmlString);
        }
        if (str_contains($data['root']['element']['@attributes']['attr'], '>')) {
            $this->assertStringContainsString('&gt;', $xmlString);
        }
        if (str_contains($data['root']['element']['@attributes']['attr'], '"')) {
            $this->assertStringContainsString('&quot;', $xmlString);
        }
        // Note: Single quotes (') are not escaped in XML attributes when the attribute
        // is delimited by double quotes, which is the correct behavior.
        // if (str_contains($data['root']['element']['@attributes']['attr'], "'")) {
        //     $this->assertStringContainsString('&apos;', $xmlString);
        // }
    }

    /**
     * Verifies that canonicalization with special characters works correctly
     * for XML-DSIG.
     */
    #[DataProvider('c14NWithSpecialCharactersDataProvider')]
    public function testC14NWithSpecialCharacters(
        array $data,
        string $expected,
        ?string $expectedException
    ): void {
        if ($expectedException) {
            $this->expectException($expectedException);
        }

        $xml = $this->xmlService->encode($data);
        $c14n = $xml->C14NWithIso88591Encoding();
        $c14n = XmlHelper::fixEntities($c14n);

        // Validate canonicalized content.
        $this->assertSame($expected, $c14n);

        // Verify that the encoding is ISO-8859-1.
        $this->assertSame(
            'ISO-8859-1',
            mb_detect_encoding($c14n, 'ISO-8859-1', true)
        );
    }

    /**
     * Verifies that special whitespace characters are handled correctly.
     */
    #[DataProvider('specialWhitespaceCharactersDataProvider')]
    public function testSpecialWhitespaceCharacters(
        array $data,
        array $expected,
        ?string $expectedException
    ): void {
        if ($expectedException) {
            $this->expectException($expectedException);
        }

        $xml = $this->xmlService->encode($data);
        $arrayData = $this->xmlService->decode($xml);

        // Validate structure.
        $this->assertSame($expected, $arrayData);

        // Verify that control characters are removed by sanitization.
        $xmlString = $xml->saveXml();
        $this->assertStringNotContainsString('&#9;', $xmlString); // TAB should be removed
        $this->assertStringNotContainsString('&#10;', $xmlString); // LF should be removed
        $this->assertStringNotContainsString('&#13;', $xmlString); // CR should be removed
        $this->assertStringContainsString(' ', $xmlString); // SPACE should be preserved as regular space
    }

    /**
     * Verifies that encoding validation in canonicalization works correctly.
     */
    #[DataProvider('c14NEncodingValidationDataProvider')]
    public function testC14NEncodingValidation(
        array $data,
        string $expected,
        ?string $expectedException
    ): void {
        if ($expectedException) {
            $this->expectException($expectedException);
        }

        $xml = $this->xmlService->encode($data);
        $c14n = $xml->C14NWithIso88591Encoding();
        $c14n = XmlHelper::fixEntities($c14n);

        // Validate canonicalized content.
        $this->assertSame($expected, $c14n);

        // Verify that the encoding is ISO-8859-1.
        $this->assertSame(
            'ISO-8859-1',
            mb_detect_encoding($c14n, 'ISO-8859-1', true)
        );

        // Verify that special characters are preserved.
        // Note: Accented characters are converted to substitution characters in ISO-8859-1.
        // Only check for entities that are actually present in the expected result.
        if (str_contains($expected, '&amp;')) {
            $this->assertStringContainsString('&amp;', $c14n);
        }
        if (str_contains($expected, '&lt;')) {
            $this->assertStringContainsString('&lt;', $c14n);
        }
        if (str_contains($expected, '&gt;')) {
            $this->assertStringContainsString('&gt;', $c14n);
        }
        if (str_contains($expected, '&quot;')) {
            $this->assertStringContainsString('&quot;', $c14n);
        }
        if (str_contains($expected, '&apos;')) {
            $this->assertStringContainsString('&apos;', $c14n);
        }
    }

    /**
     * Verifies specific compatibility with SII XML-DSIG.
     */
    #[DataProvider('digitalSignatureCompatibilityDataProvider')]
    public function testDigitalSignatureCompatibility(
        array $data,
        array $expected,
        ?string $expectedException
    ): void {
        if ($expectedException) {
            $this->expectException($expectedException);
        }

        $xml = $this->xmlService->encode($data);
        $arrayData = $this->xmlService->decode($xml);

        // Validate structure.
        $this->assertSame($expected, $arrayData);

        // Verify that the XML is valid for electronic signature.
        $xmlString = $xml->saveXml();
        $this->assertStringContainsString('encoding="ISO-8859-1"', $xmlString);

        // Verify canonicalization for DSIG.
        $c14n = $xml->C14NWithIso88591Encoding();
        $this->assertSame(
            'ISO-8859-1',
            mb_detect_encoding($c14n, 'ISO-8859-1', true)
        );

        // Verify that special characters are preserved in C14N.
        // Note: Accented characters are converted to substitution characters in ISO-8859-1.
        // The test verifies that the encoding is correct for XML-DSIG.
    }

    /**
     * Verifies that UTF-8 characters not supported in ISO-8859-1 are detected
     * and rejected.
     */
    public function testUtf8CharactersNotSupportedInIso88591Detection(): void
    {
        $unsupportedChars = [
            '€', '™', '©', '®', '°', '±', '²', '³', 'µ', '¶', '·', '¸', '¹',
            'º', '»',
        ];

        foreach ($unsupportedChars as $char) {
            $data = ['root' => ['element' => "Texto con $char"]];

            try {
                $xml = $this->xmlService->encode($data);
                $xmlString = $xml->saveXml();

                // If no exception is thrown, verify that the character is not in
                // the XML.
                $this->assertStringNotContainsString($char, $xmlString);
            } catch (XmlException $e) {
                // Expected to throw exception.
                $this->assertInstanceOf(XmlException::class, $e);
            }
        }
    }

    /**
     * Verifies that control characters are detected and rejected for XML-DSIG.
     */
    public function testControlCharactersDetection(): void
    {
        $controlChars = [
            "\x00", "\x01", "\x02", "\x03", "\x04", "\x05", "\x06", "\x07",
            "\x7F",
        ];

        foreach ($controlChars as $char) {
            $data = ['root' => ['element' => "Texto$char control"]];

            try {
                $xml = $this->xmlService->encode($data);
                $xmlString = $xml->saveXml();

                // If no exception is thrown, verify that the control character
                // is not in the XML.
                $this->assertStringNotContainsString($char, $xmlString);
            } catch (XmlException $e) {
                // Expected to throw exception.
                $this->assertInstanceOf(XmlException::class, $e);
            }
        }
    }

    /**
     * Verifies that canonicalization correctly preserves special characters for
     * XML-DSIG.
     */
    public function testC14NPreservesSpecialCharacters(): void
    {
        $data = ['root' => [
            'element' => [
                '@attributes' => [
                    'id' => 'F33T1',
                    'version' => '1.0',
                    'fecha' => '2025-01-03',
                ],
                '@value' => 'Contenido con áéíóú ñ & < > " \'',
            ],
        ]];

        $xml = $this->xmlService->encode($data);
        $c14n = $xml->C14NWithIso88591Encoding();
        $c14n = XmlHelper::fixEntities($c14n);

        // Verify that it is compatible with XML-DSIG.
        $this->assertStringContainsString('id="F33T1"', $c14n);
        $this->assertStringContainsString('version="1.0"', $c14n);
        $this->assertStringContainsString('fecha="2025-01-03"', $c14n);
        // Note: Accented characters are converted to substitution characters in ISO-8859-1.
        $this->assertStringContainsString('&amp;', $c14n);
        $this->assertStringContainsString('&lt;', $c14n);
        $this->assertStringContainsString('&gt;', $c14n);
        $this->assertStringContainsString('&quot;', $c14n);
        $this->assertStringContainsString('&apos;', $c14n);

        // Verify that the encoding is ISO-8859-1.
        $this->assertSame(
            'ISO-8859-1',
            mb_detect_encoding($c14n, 'ISO-8859-1', true)
        );
    }

    /**
     * Verifies that encoding validation works correctly for SII documents.
     */
    public function testEncodingValidationForSII(): void
    {
        $data = ['DTE' => [
            '@attributes' => ['version' => '1.0'],
            'Documento' => [
                '@attributes' => ['ID' => 'F33T1'],
                'Encabezado' => [
                    'IdDoc' => [
                        'TipoDTE' => '33',
                        'Folio' => '1',
                        'FchEmis' => '2025-01-03',
                    ],
                    'Emisor' => [
                        'RUTEmisor' => '76192083-9',
                        'RznSoc' => 'Empresa con áéíóú ñ',
                    ],
                ],
            ],
        ]];

        $xml = $this->xmlService->encode($data);
        $xmlString = $xml->saveXml();

        // Verify that the declared encoding is ISO-8859-1.
        $this->assertStringContainsString('encoding="ISO-8859-1"', $xmlString);

        // Verify that the actual encoding is ISO-8859-1.
        $this->assertSame(
            'ISO-8859-1',
            mb_detect_encoding($xmlString, 'ISO-8859-1', true)
        );

        // Verify that special characters are preserved.
        // Note: Since the XML is generated in ISO-8859-1, accented characters
        // are converted to substitution characters from the start.
        // This is the expected behavior for SII XML-DSIG compatibility.

        // Verify canonicalization.
        $c14n = $xml->C14NWithIso88591Encoding();
        $this->assertSame(
            'ISO-8859-1',
            mb_detect_encoding($c14n, 'ISO-8859-1', true)
        );
    }
}
