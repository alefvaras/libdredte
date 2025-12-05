<?php

declare(strict_types=1);

/**
 * Derafu: Translation - Translation Library with Exception Support.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\TestsTranslation;

use Derafu\Translation\TranslatableMessage;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

#[CoversClass(TranslatableMessage::class)]
final class TranslatableMessageTest extends TestCase
{
    public function testBasicIcuMessage(): void
    {
        $message = new TranslatableMessage(
            'Hello {name}',
            ['name' => 'John'],
            null,
            'en'
        );

        $this->assertSame('Hello John', (string) $message);
    }

    public function testMessageWithMultipleParameters(): void
    {
        $message = new TranslatableMessage(
            '{count, plural, one{# message} other{# messages}} from {sender}',
            [
                'count' => 5,
                'sender' => 'Admin',
            ],
            null,
            'en'
        );

        $this->assertSame('5 messages from Admin', (string) $message);
    }

    public function testMessageWithTranslator(): void
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator
            ->expects($this->once())
            ->method('trans')
            ->with(
                'hello.world',
                ['name' => 'John'],
                'messages',
                'es'
            )
            ->willReturn('¡Hola John!');

        $message = new TranslatableMessage(
            'hello.world',
            ['name' => 'John'],
            'messages',
            'en'
        );

        $this->assertSame('¡Hola John!', $message->trans($translator, 'es'));
    }

    public function testFallbackOnInvalidIcuFormat(): void
    {
        // Un mensaje ICU inválido debe retornar el mensaje original.
        $message = new TranslatableMessage(
            'Hello {name',  // Falta cerrar el placeholder.
            ['name' => 'John'],
            null,
            'en'
        );

        $this->assertSame('Hello {name', (string) $message);
    }

    public function testGenderSelect(): void
    {
        $message = new TranslatableMessage(
            '{gender, select, female{She is} male{He is} other{They are}} {status}',
            [
                'gender' => 'female',
                'status' => 'online',
            ],
            null,
            'en'
        );

        $this->assertSame('She is online', (string) $message);
    }

    public function testNestedParameters(): void
    {
        $message = new TranslatableMessage(
            'User {user} has {count, plural, one{# message} other{# messages}} in {folder}',
            [
                'user' => 'admin',
                'count' => 2,
                'folder' => 'inbox',
            ],
            null,
            'en'
        );

        $this->assertSame(
            'User admin has 2 messages in inbox',
            (string) $message
        );
    }
}
