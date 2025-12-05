<?php

declare(strict_types=1);

/**
 * Derafu: XML - Library for XML manipulation.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

return [

    // Cases for testUtf8CharactersNotSupportedInIso88591().
    'testUtf8CharactersNotSupportedInIso88591' => [
        // UTF-8 characters that do NOT exist in ISO-8859-1.
        // NOTE: Currently, the code does NOT validate these characters,
        // so NO exception is thrown.
        'euro_symbol' => [
            'data' => ['root' => ['element' => 'Precio: 100€']],
            'expected' => ['root' => ['element' => 'Precio: 100€']],
            'expectedException' => null,
        ],
        'trademark_symbol' => [
            'data' => ['root' => ['element' => 'Marca™ registrada']],
            'expected' => ['root' => ['element' => 'Marca™ registrada']],
            'expectedException' => null,
        ],
        'copyright_symbol' => [
            'data' => ['root' => ['element' => 'Derechos© reservados']],
            'expected' => ['root' => ['element' => 'Derechos© reservados']],
            'expectedException' => null,
        ],
        'registered_symbol' => [
            'data' => ['root' => ['element' => 'Marca® registrada']],
            'expected' => ['root' => ['element' => 'Marca® registrada']],
            'expectedException' => null,
        ],
        'degree_symbol' => [
            'data' => ['root' => ['element' => 'Temperatura: 25°C']],
            'expected' => ['root' => ['element' => 'Temperatura: 25°C']],
            'expectedException' => null,
        ],
        'plus_minus_symbol' => [
            'data' => ['root' => ['element' => 'Error ±5%']],
            'expected' => ['root' => ['element' => 'Error ±5%']],
            'expectedException' => null,
        ],
        'superscript_numbers' => [
            'data' => ['root' => ['element' => 'x² + y² = z²']],
            'expected' => ['root' => ['element' => 'x² + y² = z²']],
            'expectedException' => null,
        ],
        'micro_symbol' => [
            'data' => ['root' => ['element' => 'Concentración: 5µg']],
            'expected' => ['root' => ['element' => 'Concentración: 5µg']],
            'expectedException' => null,
        ],
        'multiple_unsupported' => [
            'data' => ['root' => ['element' => 'Texto con €™©®°±²³µ']],
            'expected' => ['root' => ['element' => 'Texto con €™©®°±²³µ']],
            'expectedException' => null,
        ],
    ],

    // Cases for testControlCharactersInIso88591().
    'testControlCharactersInIso88591' => [
        // Control characters that may cause problems in XML-DSIG.
        'null_character' => [
            'data' => ['root' => ['element' => "Texto\x00 con null"]],
            'expected' => ['root' => ['element' => "Texto con null"]],
            'expectedException' => null,
        ],
        'start_of_heading' => [
            'data' => ['root' => ['element' => "Texto\x01 control"]],
            'expected' => ['root' => ['element' => "Texto control"]],
            'expectedException' => null,
        ],
        'start_of_text' => [
            'data' => ['root' => ['element' => "Texto\x02 control"]],
            'expected' => ['root' => ['element' => "Texto control"]],
            'expectedException' => null,
        ],
        'end_of_text' => [
            'data' => ['root' => ['element' => "Texto\x03 control"]],
            'expected' => ['root' => ['element' => "Texto control"]],
            'expectedException' => null,
        ],
        'end_of_transmission' => [
            'data' => ['root' => ['element' => "Texto\x04 control"]],
            'expected' => ['root' => ['element' => "Texto control"]],
            'expectedException' => null,
        ],
        'enquiry' => [
            'data' => ['root' => ['element' => "Texto\x05 control"]],
            'expected' => ['root' => ['element' => "Texto control"]],
            'expectedException' => null,
        ],
        'acknowledge' => [
            'data' => ['root' => ['element' => "Texto\x06 control"]],
            'expected' => ['root' => ['element' => "Texto control"]],
            'expectedException' => null,
        ],
        'bell' => [
            'data' => ['root' => ['element' => "Texto\x07 control"]],
            'expected' => ['root' => ['element' => "Texto control"]],
            'expectedException' => null,
        ],
        'delete_character' => [
            'data' => ['root' => ['element' => "Texto\x7F control"]],
            'expected' => ['root' => ['element' => "Texto control"]],
            'expectedException' => null,
        ],
        'multiple_control_chars' => [
            'data' => ['root' => ['element' => "Texto\x00\x01\x02\x03 control"]],
            'expected' => ['root' => ['element' => "Texto control"]],
            'expectedException' => null,
        ],
    ],

    // Cases for testSpecialCharactersInAttributes().
    'testSpecialCharactersInAttributes' => [
        // Special characters in XML attributes.
        'ampersand_in_attribute' => [
            'data' => ['root' => [
                'element' => [
                    '@attributes' => ['attr' => 'valor & especial'],
                    '@value' => 'contenido',
                ],
            ]],
            'expected' => ['root' => [
                'element' => [
                    '@attributes' => ['attr' => 'valor & especial'],
                    '@value' => 'contenido',
                ],
            ]],
            'expectedException' => null,
        ],
        'quotes_in_attribute' => [
            'data' => ['root' => [
                'element' => [
                    '@attributes' => ['attr' => 'valor con "comillas" y \'apóstrofes\''],
                    '@value' => 'contenido',
                ],
            ]],
            'expected' => ['root' => [
                'element' => [
                    '@attributes' => ['attr' => 'valor con "comillas" y \'apóstrofes\''],
                    '@value' => 'contenido',
                ],
            ]],
            'expectedException' => null,
        ],
        'less_greater_in_attribute' => [
            'data' => ['root' => [
                'element' => [
                    '@attributes' => ['attr' => 'valor < 10 > 5'],
                    '@value' => 'contenido',
                ],
            ]],
            'expected' => ['root' => [
                'element' => [
                    '@attributes' => ['attr' => 'valor < 10 > 5'],
                    '@value' => 'contenido',
                ],
            ]],
            'expectedException' => null,
        ],
        'accented_chars_in_attribute' => [
            'data' => ['root' => [
                'element' => [
                    '@attributes' => ['attr' => 'áéíóú ñ'],
                    '@value' => 'contenido',
                ],
            ]],
            'expected' => ['root' => [
                'element' => [
                    '@attributes' => ['attr' => 'áéíóú ñ'],
                    '@value' => 'contenido',
                ],
            ]],
            'expectedException' => null,
        ],
        'mixed_special_chars_in_attribute' => [
            'data' => ['root' => [
                'element' => [
                    '@attributes' => ['attr' => 'áéíóú & < > " \' ñ'],
                    '@value' => 'contenido',
                ],
            ]],
            'expected' => ['root' => [
                'element' => [
                    '@attributes' => ['attr' => 'áéíóú & < > " \' ñ'],
                    '@value' => 'contenido',
                ],
            ]],
            'expectedException' => null,
        ],
    ],

    // Cases for testC14NWithSpecialCharacters().
    'testC14NWithSpecialCharacters' => [
        // Canonicalization with special characters for XML-DSIG.
        // NOTE: C14NWithIso88591Encoding() converts to ISO-8859-1,
        // so accented characters become substitution characters.
        'accented_chars_in_c14n' => [
            'data' => ['root' => [
                'element' => [
                    '@attributes' => ['id' => 'F33T1', 'version' => '1.0'],
                    '@value' => 'Contenido con áéíóú ñ',
                ],
            ]],
            'expected' => '<root><element id="F33T1" version="1.0">Contenido con ' . mb_convert_encoding('áéíóú ñ', 'ISO-8859-1', 'UTF-8') . '</element></root>',
            'expectedException' => null,
        ],
        'special_chars_in_c14n' => [
            'data' => ['root' => [
                'element' => [
                    '@attributes' => ['attr' => 'valor & < > " \''],
                    '@value' => 'contenido con & < > " \'',
                ],
            ]],
            'expected' => '<root><element attr="valor &amp; &lt; > &quot; \'">contenido con &amp; &lt; &gt; &quot; &apos;</element></root>',
            'expectedException' => null,
        ],
        'mixed_content_in_c14n' => [
            'data' => ['root' => [
                'element' => [
                    '@attributes' => ['id' => 'F33T1', 'fecha' => '2025-01-03'],
                    '@value' => 'Árbol con ñ y áéíóú & < > " \'',
                ],
            ]],
            'expected' => '<root><element fecha="2025-01-03" id="F33T1">' . mb_convert_encoding('Árbol con ñ y áéíóú', 'ISO-8859-1', 'UTF-8') . ' &amp; &lt; &gt; &quot; &apos;</element></root>',
            'expectedException' => null,
        ],
    ],

    // Cases for testSpecialWhitespaceCharacters().
    'testSpecialWhitespaceCharacters' => [
        // Special whitespace characters.
        // NOTE: Control characters (tabs, line feeds, carriage returns) are
        // removed by sanitization.
        'tab_character' => [
            'data' => ['root' => ['element' => "Texto\x09 con tab"]],
            'expected' => ['root' => ['element' => "Texto con tab"]],
            'expectedException' => null,
        ],
        'line_feed' => [
            'data' => ['root' => ['element' => "Texto\x0A con LF"]],
            'expected' => ['root' => ['element' => "Texto con LF"]],
            'expectedException' => null,
        ],
        'carriage_return' => [
            'data' => ['root' => ['element' => "Texto\x0D con CR"]],
            'expected' => ['root' => ['element' => "Texto con CR"]],
            'expectedException' => null,
        ],
        'space_character' => [
            'data' => ['root' => ['element' => "Texto\x20 con espacio"]],
            'expected' => ['root' => ['element' => "Texto\x20 con espacio"]],
            'expectedException' => null,
        ],
        'mixed_whitespace' => [
            'data' => ['root' => ['element' => "Texto\x09\x0A\x0D\x20 con espacios especiales"]],
            'expected' => ['root' => ['element' => "Texto\x20 con espacios especiales"]],
            'expectedException' => null,
        ],
    ],

    // Cases for testC14NEncodingValidation().
    'testC14NEncodingValidation' => [
        // Encoding validation in canonicalization.
        // NOTE: C14NWithIso88591Encoding() converts to ISO-8859-1,
        // so accented characters become substitution characters.
        'accented_chars_encoding' => [
            'data' => ['root' => ['element' => 'Árbol con ñ y áéíóú']],
            'expected' => '<root><element>' . mb_convert_encoding('Árbol con ñ y áéíóú', 'ISO-8859-1', 'UTF-8') . '</element></root>',
            'expectedException' => null,
        ],
        'special_chars_encoding' => [
            'data' => ['root' => ['element' => 'Texto con & < > " \'']],
            'expected' => '<root><element>Texto con &amp; &lt; &gt; &quot; &apos;</element></root>',
            'expectedException' => null,
        ],
        'mixed_chars_encoding' => [
            'data' => ['root' => ['element' => 'Árbol con ñ y & < > " \'']],
            'expected' => '<root><element>' . mb_convert_encoding('Árbol con ñ y', 'ISO-8859-1', 'UTF-8') . ' &amp; &lt; &gt; &quot; &apos;</element></root>',
            'expectedException' => null,
        ],
    ],

    // Cases for testDigitalSignatureCompatibility().
    'testDigitalSignatureCompatibility' => [
        // Specific compatibility with SII XML-DSIG.
        'sii_document_structure' => [
            'data' => ['DTE' => [
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
            ]],
            'expected' => ['DTE' => [
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
            ]],
            'expectedException' => null,
        ],
        'sii_with_special_chars' => [
            'data' => ['DTE' => [
                '@attributes' => ['version' => '1.0'],
                'Documento' => [
                    '@attributes' => ['ID' => 'F33T1'],
                    'Detalle' => [
                        'NmbItem' => 'Producto con áéíóú ñ & < > " \'',
                        'DscItem' => 'Descripción con caracteres especiales',
                    ],
                ],
            ]],
            'expected' => ['DTE' => [
                '@attributes' => ['version' => '1.0'],
                'Documento' => [
                    '@attributes' => ['ID' => 'F33T1'],
                    'Detalle' => [
                        'NmbItem' => 'Producto con áéíóú ñ & < > " \'',
                        'DscItem' => 'Descripción con caracteres especiales',
                    ],
                ],
            ]],
            'expectedException' => null,
        ],
    ],

];
