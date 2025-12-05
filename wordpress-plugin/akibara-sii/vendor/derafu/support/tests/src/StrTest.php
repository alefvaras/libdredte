<?php

declare(strict_types=1);

/**
 * Derafu: Support - Essential PHP Utilities.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\TestsSupport;

use Derafu\Support\Arr;
use Derafu\Support\Str;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(Str::class)]
#[CoversClass(Arr::class)]
class StrTest extends TestCase
{
    #[Test]
    #[DataProvider('provideWordWrapData')]
    public function shouldWrapWords(
        string $input,
        int $width,
        string $break,
        bool $cut,
        string $expected
    ): void {
        $result = Str::wordWrap($input, $width, $break, $cut);
        $this->assertSame($expected, $result);
    }

    public static function provideWordWrapData(): array
    {
        return [
            'simple wrap' => [
                'The quick brown fox jumps over the lazy dog',
                20,
                "\n",
                false,
                "The quick brown fox\njumps over the lazy\ndog",
            ],
            'long string without spaces' => [
                'aaaaabbbbbcccccdddddeeeeefffffggggghhhhhiiiiijjjjj',
                10,
                "\n",
                true,
                "aaaaabbbbb\ncccccddddd\neeeeefffff\nggggghhhhh\niiiiijjjjj",
            ],
            'with long word' => [
                'The quick brown supercalifragilisticexpialidocious fox',
                20,
                "\n",
                true,
                "The quick brown\nsupercalifragilistic\nexpialidocious fox",
            ],
            'no cut long word' => [
                'The quick brown supercalifragilisticexpialidocious fox',
                20,
                "\n",
                false,
                "The quick brown\nsupercalifragilisticexpialidocious\nfox",
            ],
            'custom break' => [
                'The quick brown fox jumps over the lazy dog',
                20,
                "<br>",
                false,
                "The quick brown fox<br>jumps over the lazy<br>dog",
            ],
            'existing breaks' => [
                "Line one\nLine two that is longer\nLine three",
                20,
                "\n",
                false,
                "Line one\nLine two that is\nlonger\nLine three",
            ],
            'short text' => [
                'Short text',
                20,
                "\n",
                false,
                'Short text',
            ],
            'utf8 text' => [
                'El rápido zorro marrón salta sobre el perro perezoso',
                20,
                "\n",
                false,
                "El rápido zorro\nmarrón salta sobre\nel perro perezoso",
            ],
        ];
    }

    #[Test]
    #[DataProvider('provideStudlyCaseData')]
    public function shouldConvertToStudlyCase(string $input, string $expected): void
    {
        $result = Str::studly($input);
        $this->assertSame($expected, $result);
    }

    public static function provideStudlyCaseData(): array
    {
        return [
            'simple string' => [
                'hello world',
                'HelloWorld',
            ],
            'snake case' => [
                'hello_world',
                'HelloWorld',
            ],
            'kebab case' => [
                'hello-world',
                'HelloWorld',
            ],
            'camel case' => [
                'helloWorld',
                'HelloWorld',
            ],
            'multiple words' => [
                'hello_beautiful_world',
                'HelloBeautifulWorld',
            ],
            'already studly' => [
                'HelloWorld',
                'HelloWorld',
            ],
            'single word' => [
                'hello',
                'Hello',
            ],
            'with numbers' => [
                'hello_world_123',
                'HelloWorld123',
            ],
            'mixed delimiters' => [
                'hello-world_someCase',
                'HelloWorldSomeCase',
            ],
        ];
    }

    #[Test]
    #[DataProvider('provideCamelCaseData')]
    public function shouldConvertToCamelCase(string $input, string $expected): void
    {
        $result = Str::camel($input);
        $this->assertSame($expected, $result);
    }

    public static function provideCamelCaseData(): array
    {
        return [
            'simple string' => [
                'hello world',
                'helloWorld',
            ],
            'snake case' => [
                'hello_world',
                'helloWorld',
            ],
            'kebab case' => [
                'hello-world',
                'helloWorld',
            ],
            'mixed case' => [
                'HelloWorld',
                'helloWorld',
            ],
            'multiple words' => [
                'hello_beautiful_world',
                'helloBeautifulWorld',
            ],
            'already camel' => [
                'helloWorld',
                'helloWorld',
            ],
            'single word' => [
                'hello',
                'hello',
            ],
            'with numbers' => [
                'hello_world_123',
                'helloWorld123',
            ],
        ];
    }

    #[Test]
    #[DataProvider('provideSnakeCaseData')]
    public function shouldConvertToSnakeCase(
        string $input,
        string $expected,
        string $delimiter
    ): void {
        $result = Str::snake($input, $delimiter);
        $this->assertSame($expected, $result);
    }

    public static function provideSnakeCaseData(): array
    {
        return [
            'simple string' => [
                'hello world',
                'hello_world',
                '_',
            ],
            'camel case' => [
                'helloWorld',
                'hello_world',
                '_',
            ],
            'studly case' => [
                'HelloWorld',
                'hello_world',
                '_',
            ],
            'kebab case' => [
                'hello-world',
                'hello_world',
                '_',
            ],
            'multiple words' => [
                'helloBeautifulWorld',
                'hello_beautiful_world',
                '_',
            ],
            'already snake' => [
                'hello_world',
                'hello_world',
                '_',
            ],
            'single word' => [
                'hello',
                'hello',
                '_',
            ],
            'with numbers' => [
                'helloWorld123',
                'hello_world123',
                '_',
            ],
            'custom delimiter' => [
                'helloWorld',
                'hello-world',
                '-',
            ],
        ];
    }

    #[Test]
    public function shouldGenerateValidUuid4(): void
    {
        $uuid = Str::uuid4();

        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/',
            $uuid
        );
    }

    #[Test]
    #[DataProvider('providePlaceholderData')]
    public function shouldFormat(
        string $template,
        array $data,
        string $expected
    ): void {
        $result = Str::format($template, $data, 'mustache|sql');
        $this->assertSame($expected, $result);
    }

    public static function providePlaceholderData(): array
    {
        return [
            'simple replacement' => [
                'Hello :name!',
                ['name' => 'John'],
                'Hello John!',
            ],
            'multiple replacements' => [
                'Hello :name, you are :age years old',
                ['name' => 'John', 'age' => 25],
                'Hello John, you are 25 years old',
            ],
            'nested replacements' => [
                'Hello {{user.name}}!',
                ['user' => ['name' => 'John']],
                'Hello John!',
            ],
            'no replacements needed' => [
                'Hello World!',
                [],
                'Hello World!',
            ],
            'replacement with numbers' => [
                'Count: :count',
                ['count' => 42],
                'Count: 42',
            ],
        ];
    }

    #[Test]
    #[DataProvider('provideSlugData')]
    public function shouldSlugStrings(
        string $input,
        string $expected
    ): void {
        $result = Str::slug($input);
        $this->assertSame($expected, $result);
    }

    public static function provideSlugData(): array
    {
        return [
            'simple text' => [
                'Hello World',
                'hello-world',
            ],
            'accented characters' => [
                'áéíóúñ',
                'aeioun',
            ],
            'special characters' => [
                'Hello & World!',
                'hello-and-world',
            ],
            'multiple spaces' => [
                'Hello    World',
                'hello-world',
            ],
            'multiple hyphens' => [
                'hello---world',
                'hello-world',
            ],
        ];
    }

    #[Test]
    #[DataProvider('provideExtractData')]
    public function shouldExtractSubstrings(
        string $text,
        string $start,
        string $end,
        int $offset,
        ?array $expected
    ): void {
        $result = Str::extract($text, $start, $end, $offset);
        $this->assertSame($expected, $result);
    }

    public static function provideExtractData(): array
    {
        return [
            'simple extraction' => [
                'Hello [world] there',
                '[',
                ']',
                0,
                [
                    'string' => 'world',
                    'start' => 7,
                    'end' => 11,
                    'length' => 5,
                ],
            ],
            'not found' => [
                'Hello world',
                '[',
                ']',
                0,
                null,
            ],
            'with offset' => [
                'one [two] three [four]',
                '[',
                ']',
                10,
                [
                    'string' => 'four',
                    'start' => 17,
                    'end' => 20,
                    'length' => 4,
                ],
            ],
        ];
    }

    #[Test]
    #[DataProvider('provideEncodingData')]
    public function shouldHandleUtf8Encoding(
        string $latinString,     // String en ISO-8859-1.
        string $utf8String       // El mismo string en UTF-8.
    ): void {
        // Probar utf8encode (ISO-8859-1 -> UTF-8).
        $encoded = Str::utf8encode($latinString);
        $this->assertSame($utf8String, $encoded);
        $this->assertTrue(mb_check_encoding($encoded, 'UTF-8'));

        // Probar utf8decode (UTF-8 -> ISO-8859-1).
        $decoded = Str::utf8decode($utf8String);
        $this->assertSame($latinString, $decoded);
        $this->assertTrue(mb_check_encoding($decoded, 'ISO-8859-1'));
    }

    public static function provideEncodingData(): array
    {
        return [
            'simple ascii' => ['Hello', 'Hello'],   // ASCII puro no cambia.
            'empty string' => ['', ''],             // String vacío no cambia.
            'spanish chars' => [
                "El ni\xf1o cumpli\xf3 15 a\xf1os", // ISO-8859-1
                "El niño cumplió 15 años",           // UTF-8
            ],
            'accented chars' => [
                "cr\xe8me br\xfbl\xe9e",            // ISO-8859-1
                "crème brûlée",                      // UTF-8
            ],
            'special chars' => [
                "\xa1Hola Se\xf1or!",               // ISO-8859-1
                "¡Hola Señor!",                      // UTF-8
            ],
            'mixed content' => [
                "Caf\xe9 $5.00 \xa1Oferta!",        // ISO-8859-1
                "Café $5.00 ¡Oferta!",               // UTF-8
            ],
        ];
    }

    public function testStrUtf2IsoInvalidEncoding(): void
    {
        // Secuencia UTF-8 inválida.
        $invalidUtf8String = "\x80\x81\x82";

        $result = Str::utf8decode($invalidUtf8String);

        // El resultado debe ser el string origial pues no puede ser convertido.
        $this->assertSame($invalidUtf8String, $result);
    }

    public function testStrIso2UtfInvalidEncoding(): void
    {
        // Secuencia ISO-8859-1 inválida.
        $invalidIsoString = "\xFF\xFE\xFD";

        // A pesar de ser un string con secuencia inválida, la función
        // mb_convert_encoding() (usada en Str::utf8decode()) hará un "mejor"
        // esfuerzo y entregará el siguiente string.
        $expectedString = 'ÿþý';

        $result = Str::utf8encode($invalidIsoString);

        $this->assertSame($expectedString, $result);
    }

    #[Test]
    #[DataProvider('provideRandomData')]
    public function shouldGenerateRandomStrings(
        int $length,
        bool $useUppercase,
        bool $useNumbers,
        bool $useSpecial,
        string $pattern
    ): void {
        $result = Str::random($length, $useUppercase, $useNumbers, $useSpecial);

        $this->assertSame($length, strlen($result));
        $this->assertMatchesRegularExpression($pattern, $result);
    }

    public static function provideRandomData(): array
    {
        return [
            'lowercase only' => [
                10,
                false,
                false,
                false,
                '/^[a-z]{10}$/',
            ],
            'with uppercase' => [
                8,
                true,
                false,
                false,
                '/^[a-zA-Z]{8}$/',
            ],
            'with numbers' => [
                12,
                true,
                true,
                false,
                '/^[a-zA-Z0-9]{12}$/',
            ],
            'with special chars' => [
                15,
                true,
                true,
                true,
                '/^[a-zA-Z0-9!@#$%^&*()_+\-=\[\]{}|;:,.<>?]{15}$/',
            ],
        ];
    }
}
