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

use Derafu\Translation\Abstract\AbstractMessageProvider;
use Derafu\Translation\Provider\PhpMessageProvider;
use Derafu\Translation\Translator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Translator::class)]
#[CoversClass(AbstractMessageProvider::class)]
#[CoversClass(PhpMessageProvider::class)]
final class TranslatorTest extends TestCase
{
    private Translator $translator;

    private string $fixturesDir;

    protected function setUp(): void
    {
        $this->fixturesDir = __DIR__ . '/../fixtures/translations';
        $provider = new PhpMessageProvider($this->fixturesDir);
        $this->translator = new Translator(
            $provider,
            'en',
            ['en', 'es']
        );
    }

    public function testBasicTranslation(): void
    {
        $this->assertSame(
            'Welcome John!',
            $this->translator->trans('welcome', ['name' => 'John'])
        );

        $this->assertSame(
            '¡Bienvenido Juan!',
            $this->translator->trans(
                'welcome',
                ['name' => 'Juan'],
                null,
                'es'
            )
        );
    }

    public function testPluralizations(): void
    {
        // Test zero case.
        $this->assertSame(
            'No messages',
            $this->translator->trans('messages', ['count' => 0])
        );

        // Test singular case.
        $this->assertSame(
            'One message',
            $this->translator->trans('messages', ['count' => 1])
        );

        // Test plural case.
        $this->assertSame(
            '5 messages',
            $this->translator->trans('messages', ['count' => 5])
        );
    }

    public function testSelect(): void
    {
        $value = [
            'small' => 5,
            'medium' => 30,
            'large' => 100,
        ];

        foreach ($value as $range => $val) {
            $this->assertSame(
                match($range) {
                    'small' => '0-10',
                    'medium' => '11-50',
                    'large' => '>50'
                },
                $this->translator->trans('range', ['value' => $range])
            );
        }
    }

    public function testComplexTemplate(): void
    {
        $this->assertSame(
            'She works 2 times',
            $this->translator->trans('template', [
                'gender' => 'female',
                'action' => 'works',
                'count' => 2,
            ])
        );

        $this->assertSame(
            'Él trabaja 1 vez',
            $this->translator->trans('template', [
                'gender' => 'male',
                'action' => 'trabaja',
                'count' => 1,
            ], null, 'es')
        );
    }

    public function testDifferentDomain(): void
    {
        $this->assertSame(
            'The field email is required',
            $this->translator->trans(
                'validation.required',
                ['field' => 'email'],
                'errors'
            )
        );

        $this->assertSame(
            'El campo email es requerido',
            $this->translator->trans(
                'validation.required',
                ['field' => 'email'],
                'errors',
                'es'
            )
        );
    }

    public function testLocaleFallback(): void
    {
        // Configurar un locale que no existe, debería caer en el fallback.
        $this->translator->setLocale('fr');

        $this->assertSame(
            'Welcome John!',
            $this->translator->trans('welcome', ['name' => 'John'])
        );
    }

    public function testMissingTranslation(): void
    {
        // Cuando no existe la traducción, debe devolver el key.
        $this->assertSame(
            'missing.key',
            $this->translator->trans('missing.key')
        );
    }

    public function testInvalidIcuMessage(): void
    {
        // Simular un mensaje ICU inválido.
        $this->assertSame(
            'Invalid {format',
            $this->translator->trans('Invalid {format', ['value' => 'test'])
        );
    }
}
