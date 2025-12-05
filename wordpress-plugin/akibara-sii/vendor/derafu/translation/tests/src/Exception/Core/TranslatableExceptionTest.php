<?php

declare(strict_types=1);

/**
 * Derafu: Translation - Translation Library with Exception Support.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\TestsTranslation\Exception\Core;

use Derafu\Translation\Contract\TranslatableInterface;
use Derafu\Translation\Exception\Core\TranslatableException;
use Derafu\Translation\TranslatableMessage;
use Exception;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;
use Throwable;

#[CoversClass(TranslatableException::class)]
#[CoversClass(TranslatableMessage::class)]
final class TranslatableExceptionTest extends TestCase
{
    private MockObject&TranslatorInterface $translator;

    protected function setUp(): void
    {
        $this->translator = $this->createMock(TranslatorInterface::class);
    }

    public function testConstructWithParameters(): void
    {
        $exception = new TranslatableException([
            'error.test',
            'param' => 'value',
        ]);

        $this->translator
            ->expects($this->once())
            ->method('trans')
            ->with(
                'error.test',
                ['param' => 'value'],
                'errors',
                'en'
            )
            ->willReturn('Test with value');

        $this->assertSame(
            'Test with value',
            $exception->trans($this->translator)
        );
    }

    public function testConstructWithEmptyArray(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Message array cannot be empty.');

        new TranslatableException([]);
    }

    public function testConstructWithInvalidArrayFirstElement(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'First element of message array must be a string.'
        );

        new TranslatableException([123, 'param' => 'value']);
    }

    public function testConstructWithPreviousException(): void
    {
        $previous = new Exception('Previous error');
        $exception = new TranslatableException(
            'Test message',
            0,
            $previous
        );

        $this->assertSame($previous, $exception->getPrevious());
    }

    public function testIcuFormatting(): void
    {
        $exception = new TranslatableException([
            'Error with {param}',
            'param' => 'test',
        ]);

        // Test default ICU formatting without translator.
        $this->assertSame(
            'Error with test',
            $exception->getMessage()
        );
    }

    public function testExceptionInheritance(): void
    {
        $exception = new TranslatableException('Test');

        $this->assertInstanceOf(Exception::class, $exception);
        $this->assertInstanceOf(Throwable::class, $exception);
        $this->assertInstanceOf(TranslatableInterface::class, $exception);
    }

    public function testConstructWithString(): void
    {
        $exception = new class ('validation.required') extends TranslatableException {};

        $this->translator
            ->expects($this->once())
            ->method('trans')
            ->with(
                'validation.required',
                [],
                'errors',
                'en'
            )
            ->willReturn('This field is required');

        $this->assertSame(
            'This field is required',
            $exception->trans($this->translator)
        );
    }

    public function testConstructWithArrayParameters(): void
    {
        $exception = new class ([
            'validation.min_length',
            'field' => 'password',
            'min' => 8,
        ]) extends TranslatableException {};

        $this->translator
            ->expects($this->once())
            ->method('trans')
            ->with(
                'validation.min_length',
                ['field' => 'password', 'min' => 8],
                'errors',
                'en'
            )
            ->willReturn('The password must be at least 8 characters');

        $this->assertSame(
            'The password must be at least 8 characters',
            $exception->trans($this->translator)
        );
    }

    public function testConstructWithTranslatableMessage(): void
    {
        $message = new TranslatableMessage(
            'validation.email',
            ['email' => 'test@example.com'],
            'errors',
            'en'
        );

        $exception = new class ($message) extends TranslatableException {};

        $this->translator
            ->expects($this->once())
            ->method('trans')
            ->with(
                'validation.email',
                ['email' => 'test@example.com'],
                'errors',
                'en'
            )
            ->willReturn('Invalid email: test@example.com');

        $this->assertSame(
            'Invalid email: test@example.com',
            $exception->trans($this->translator)
        );
    }

    public function testConstructWithArrayWithoutString(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('First element of message array must be a string.');

        new class ([123, 'param' => 'value']) extends TranslatableException {};
    }

    public function testMessageWithoutTranslator(): void
    {
        $exception = new class ([
            'The field {field} must be at least {min} characters',
            'field' => 'password',
            'min' => 8,
        ]) extends TranslatableException {};

        // Sin traductor, debe usar ICU directamente.
        $this->assertSame(
            'The field password must be at least 8 characters',
            $exception->getMessage()
        );
    }

    public function testCustomDomainAndLocale(): void
    {
        $exception = new class ('custom.message') extends TranslatableException {
            protected string $defaultDomain = 'custom';

            protected string $defaultLocale = 'es';
        };

        $this->translator
            ->expects($this->once())
            ->method('trans')
            ->with(
                'custom.message',
                [],
                'custom',
                'es'
            )
            ->willReturn('Mensaje personalizado');

        $this->assertSame(
            'Mensaje personalizado',
            $exception->trans($this->translator)
        );
    }
}
