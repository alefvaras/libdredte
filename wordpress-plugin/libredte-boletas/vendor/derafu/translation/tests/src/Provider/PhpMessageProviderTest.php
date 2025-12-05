<?php

declare(strict_types=1);

/**
 * Derafu: Translation - Translation Library with Exception Support.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\TestsTranslation\Provider;

use Derafu\Translation\Abstract\AbstractMessageProvider;
use Derafu\Translation\Provider\PhpMessageProvider;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;

#[CoversClass(PhpMessageProvider::class)]
#[CoversClass(AbstractMessageProvider::class)]
final class PhpMessageProviderTest extends TestCase
{
    private string $fixturesDir;

    protected function setUp(): void
    {
        $this->fixturesDir = __DIR__ . '/../../fixtures/translations';
    }

    public function testGetMessages(): void
    {
        $provider = new PhpMessageProvider($this->fixturesDir);

        $messages = $provider->getMessages('en');
        $this->assertCount(5, $messages);
        $this->assertSame('Welcome {name}!', $messages['welcome']);
        $this->assertSame('Goodbye {name}!', $messages['goodbye']);

        // Verificar que los mensajes complejos se carguen correctamente.
        $this->assertArrayHasKey('messages', $messages);
        $this->assertArrayHasKey('range', $messages);
        $this->assertArrayHasKey('template', $messages);
    }

    public function testGetMessagesForNonExistentLocale(): void
    {
        $provider = new PhpMessageProvider($this->fixturesDir);

        $messages = $provider->getMessages('fr');
        $this->assertEmpty($messages);
    }

    public function testGetAvailableLocales(): void
    {
        $provider = new PhpMessageProvider($this->fixturesDir);

        // No se usa 'messages' porque existe el archivo 'invalid' y lo toma
        // como "locale".
        $locales = $provider->getAvailableLocales('errors');

        sort($locales); // Asegurar orden consistente.
        $this->assertCount(2, $locales);
        $this->assertSame(['en', 'es'], $locales);
    }

    public function testInvalidDirectory(): void
    {
        $this->expectException(RuntimeException::class);
        new PhpMessageProvider('/invalid/directory');
    }

    public function testInvalidPhpFile(): void
    {
        $provider = new PhpMessageProvider($this->fixturesDir);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'File "' . $this->fixturesDir . '/messages/invalid.php" must return an array, got string.'
        );
        $provider->getMessages('invalid');
    }

    public function testMultipleDomains(): void
    {
        $provider = new PhpMessageProvider($this->fixturesDir);

        // Probar dominio messages.
        $messages = $provider->getMessages('en', 'messages');
        $this->assertCount(5, $messages);
        $this->assertArrayHasKey('welcome', $messages);

        // Probar dominio errors.
        $errors = $provider->getMessages('en', 'errors');
        $this->assertCount(3, $errors);
        $this->assertArrayHasKey('validation.required', $errors);
    }

    public function testMessagesAreArray(): void
    {
        $provider = new PhpMessageProvider($this->fixturesDir);
        $messages = $provider->getMessages('en');

        $this->assertIsArray($messages);
        foreach ($messages as $key => $value) {
            $this->assertIsString($key);
            $this->assertIsString($value);
        }
    }
}
